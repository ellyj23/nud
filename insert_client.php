<?php
// --- Enhanced Error Reporting for Debugging ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied']);
    exit;
}

require_once 'db.php';
require_once 'rbac.php';
require_once 'activity_logger.php';
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Configuration error: PDO connection object not found in db.php.']);
    exit;
}

// Check permission to create clients
if (!userHasPermission($_SESSION['user_id'], 'create-client')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied. You do not have permission to create clients.']);
    exit;
}

// Sanitize and retrieve POST data using modern, safe methods
$reg_no = isset($_POST['reg_no']) ? htmlspecialchars(trim($_POST['reg_no']), ENT_QUOTES, 'UTF-8') : '';
$client_name = isset($_POST['client_name']) ? htmlspecialchars(trim($_POST['client_name']), ENT_QUOTES, 'UTF-8') : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$Responsible = isset($_POST['Responsible']) ? htmlspecialchars(trim($_POST['Responsible']), ENT_QUOTES, 'UTF-8') : '';
$TIN = isset($_POST['TIN']) ? htmlspecialchars(trim($_POST['TIN']), ENT_QUOTES, 'UTF-8') : '';
$service = isset($_POST['service']) ? htmlspecialchars(trim($_POST['service']), ENT_QUOTES, 'UTF-8') : '';
$currency = isset($_POST['currency']) ? htmlspecialchars(trim($_POST['currency']), ENT_QUOTES, 'UTF-8') : '';

// For numeric values, filter_input remains a great choice for validation
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$paid_amount = filter_input(INPUT_POST, 'paid_amount', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);

// Validate TIN if provided - must be numeric and max 9 digits
if (!empty($TIN) && (!ctype_digit($TIN) || strlen($TIN) > 9)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'TIN must be numeric and up to 9 digits.']);
    exit;
}

// Basic validation
if (empty($client_name) || empty($date) || $amount === false || $paid_amount === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input. Client Name, Date, Amount, and Paid Amount are required.']);
    exit;
}

// Calculate due amount and status
$due_amount = $amount - $paid_amount;
$status = 'NOT PAID';
if ($paid_amount >= $amount && $amount > 0) {
    $status = 'PAID';
    $due_amount = 0; // Ensure due amount is not negative
} elseif ($paid_amount > 0) {
    $status = 'PARTIALLY PAID';
}

try {
    $pdo->beginTransaction();

    // Check for duplicate reg_no considering year and service type
    // Same reg_no is allowed if the year (from date) or service type is different
    if (!empty($reg_no)) {
        $checkSql = "SELECT COUNT(*) FROM clients 
                     WHERE reg_no = :reg_no 
                     AND YEAR(date) = YEAR(:date) 
                     AND service = :service";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':reg_no' => $reg_no,
            ':date' => $date,
            ':service' => $service
        ]);
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Duplicate Registration Number: This reg no with the same service type already exists for this year'
            ]);
            exit;
        }
    }

    $sql = "INSERT INTO clients (reg_no, client_name, date, Responsible, TIN, service, amount, currency, paid_amount, due_amount, status) 
            VALUES (:reg_no, :client_name, :date, :Responsible, :TIN, :service, :amount, :currency, :paid_amount, :due_amount, :status)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':reg_no' => $reg_no,
        ':client_name' => $client_name,
        ':date' => $date,
        ':Responsible' => $Responsible,
        ':TIN' => !empty($TIN) ? $TIN : null,
        ':service' => $service,
        ':amount' => $amount,
        ':currency' => $currency,
        ':paid_amount' => $paid_amount,
        ':due_amount' => $due_amount,
        ':status' => $status
    ]);
    $clientId = $pdo->lastInsertId();

    $historySql = "INSERT INTO client_history (client_id, user_name, action, details) VALUES (:client_id, :user_name, :action, :details)";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([
        ':client_id' => $clientId,
        ':user_name' => $_SESSION['username'] ?? 'System',
        ':action' => 'CREATE',
        ':details' => "Client record created with amount: $amount $currency."
    ]);

    $pdo->commit();
    
    // Log activity
    logActivity($_SESSION['user_id'] ?? null, 'create-client', 'clients', $clientId, [
        'client_name' => $client_name,
        'amount' => $amount,
        'currency' => $currency
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Client added successfully!']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    $errorInfo = $e->errorInfo;
    echo json_encode([
        'success' => false,
        'error' => 'Database error during insert.',
        'details' => $e->getMessage(),
        'sql_error_code' => $errorInfo[1] ?? null,
        'sql_error_message' => $errorInfo[2] ?? null,
    ]);
}
?>