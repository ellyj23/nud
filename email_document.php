<?php
/**
 * Email Document Feature
 * Allows sending generated documents directly to recipient's email
 */

session_start();
require_once 'db.php';
require_once 'fpdf/fpdf.php';
require_once 'lib/QRCodeGenerator.php';
require_once 'lib/BarcodeGenerator.php';
require_once 'lib/EmailTemplates.php';

// Check authentication
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    die(json_encode(['success' => false, 'message' => 'Authentication required']));
}

$user_id = $_SESSION['user_id'];

// Get POST data
$doc_type = $_POST['doc_type'] ?? '';
$doc_id = intval($_POST['doc_id'] ?? 0);
$recipient_email = $_POST['recipient_email'] ?? '';
$recipient_name = $_POST['recipient_name'] ?? '';
$subject = $_POST['subject'] ?? '';
$message = $_POST['message'] ?? '';
$cc_emails = $_POST['cc_emails'] ?? '';

// Validate inputs
if (empty($doc_type) || $doc_id <= 0 || empty($recipient_email)) {
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

// Validate email
if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success' => false, 'message' => 'Invalid recipient email address']));
}

try {
    // Generate PDF in memory
    ob_start();
    $_GET['type'] = $doc_type;
    $_GET['id'] = $doc_id;
    
    // Include and execute PDF generation
    include 'generate_pdf.php';
    $pdf_content = ob_get_clean();
    
    // Generate filename
    $filename = ucfirst($doc_type) . '-' . $doc_id . '-' . date('Ymd') . '.pdf';
    
    // Prepare email
    $sender_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
    $sender_email = $_SESSION['email'];
    
    // Default subject if not provided
    if (empty($subject)) {
        $subject = ucfirst($doc_type) . " #$doc_id from Feza Logistics";
    }
    
    // Default message if not provided
    if (empty($message)) {
        $message = "Dear $recipient_name,\n\nPlease find attached your " . strtolower($doc_type) . " (#$doc_id) from Feza Logistics.\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n$sender_name\nFeza Logistics";
    }
    
    // Create HTML email body using professional templates
    $html_message = EmailTemplates::getTemplate($doc_type, [
        'recipient_name' => $recipient_name,
        'doc_type' => $doc_type,
        'doc_id' => $doc_id,
        'sender_name' => $sender_name,
        'message' => $message
    ]);
    
    // Email headers
    $boundary = md5(time());
    $headers = "From: Feza Logistics <no-reply@fezalogistics.com>\r\n";
    $headers .= "Reply-To: $sender_email\r\n";
    
    // Add CC if provided
    if (!empty($cc_emails)) {
        $cc_list = array_map('trim', explode(',', $cc_emails));
        $valid_cc = array_filter($cc_list, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        if (!empty($valid_cc)) {
            $headers .= "Cc: " . implode(', ', $valid_cc) . "\r\n";
        }
    }
    
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    
    // Email body
    $email_body = "--{$boundary}\r\n";
    $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $email_body .= $html_message . "\r\n\r\n";
    
    // Attach PDF
    $email_body .= "--{$boundary}\r\n";
    $email_body .= "Content-Type: application/pdf; name=\"{$filename}\"\r\n";
    $email_body .= "Content-Transfer-Encoding: base64\r\n";
    $email_body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $email_body .= chunk_split(base64_encode($pdf_content)) . "\r\n";
    $email_body .= "--{$boundary}--";
    
    // Send email with retry mechanism
    $mail_sent = false;
    $mail_error = '';
    $max_retries = 3;
    
    for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
        // Clear any previous errors
        error_clear_last();
        
        // Attempt to send email
        $mail_sent = @mail($recipient_email, $subject, $email_body, $headers);
        
        if ($mail_sent) {
            break; // Success, exit retry loop
        }
        
        // Get the last error if available
        $last_error = error_get_last();
        $mail_error = $last_error ? $last_error['message'] : 'Mail function returned false';
        
        // Log retry attempt
        error_log("Email send attempt {$attempt}/{$max_retries} failed for {$recipient_email}: {$mail_error}");
        
        if ($attempt < $max_retries) {
            // Wait before retrying (exponential backoff)
            usleep($attempt * 500000); // 0.5s, 1s, 1.5s
        }
    }
    
    // Log email attempt in database
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO email_logs 
            (user_id, doc_type, doc_id, recipient_email, recipient_name, cc_emails, 
             subject, message_body, attachment_name, attachment_size, email_type, 
             status, error_message, ip_address)
            VALUES 
            (:user_id, :doc_type, :doc_id, :recipient_email, :recipient_name, :cc_emails,
             :subject, :message_body, :attachment_name, :attachment_size, :email_type,
             :status, :error_message, :ip_address)
        ");
        
        $log_stmt->execute([
            ':user_id' => $user_id,
            ':doc_type' => $doc_type,
            ':doc_id' => $doc_id,
            ':recipient_email' => $recipient_email,
            ':recipient_name' => $recipient_name,
            ':cc_emails' => $cc_emails,
            ':subject' => $subject,
            ':message_body' => $message,
            ':attachment_name' => $filename,
            ':attachment_size' => strlen($pdf_content),
            ':email_type' => 'document',
            ':status' => $mail_sent ? 'sent' : 'failed',
            ':error_message' => $mail_sent ? null : $mail_error,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log email: " . $e->getMessage());
    }
    
    if ($mail_sent) {
        // Log activity
        try {
            if (file_exists(__DIR__ . '/activity_logger.php')) {
                require_once __DIR__ . '/activity_logger.php';
                logActivity($user_id, 'email-document', $doc_type, $doc_id, 
                           json_encode([
                               'recipient' => $recipient_email,
                               'subject' => $subject
                           ]));
            }
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to log email activity: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Document sent successfully to ' . $recipient_email
        ]);
    } else {
        // Provide more detailed error message
        $errorDetail = 'The email could not be sent.';
        
        // Check common issues
        if (strpos($mail_error, 'SMTP') !== false) {
            $errorDetail = 'SMTP configuration error. Please contact support.';
        } elseif (strpos($mail_error, 'connection') !== false) {
            $errorDetail = 'Mail server connection failed. Please try again later.';
        } elseif (empty(ini_get('sendmail_path')) && empty(ini_get('SMTP'))) {
            $errorDetail = 'Mail server not configured. Please contact support.';
        }
        
        error_log("Email send failed for doc {$doc_type}#{$doc_id} to {$recipient_email}: {$mail_error}");
        
        echo json_encode([
            'success' => false,
            'message' => $errorDetail . ' If the problem persists, please contact support with error reference: ' . date('YmdHis')
        ]);
    }
    
} catch (Exception $e) {
    error_log("Email document error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while preparing the document. Please try again or contact support.'
    ]);
}
?>
