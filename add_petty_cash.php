<?php
/**
 * add_petty_cash.php
 * 
 * API endpoint for petty cash CRUD operations
 * Handles: create, update, and delete operations
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// --- HELPER FUNCTION ---
function send_json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    send_json_response(['success' => false, 'error' => 'Authentication required.'], 401);
}

require_once 'db.php';

// Check if database connection was successful
if ($pdo === null) {
    // Database connection failed, return the error from db.php
    if (isset($db_connection_error)) {
        send_json_response($db_connection_error, 500);
    } else {
        send_json_response([
            'success' => false,
            'error' => 'Database connection is not available'
        ], 500);
    }
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Check if JSON decoding failed
if (json_last_error() !== JSON_ERROR_NONE) {
    send_json_response([
        'success' => false,
        'error' => 'Invalid JSON data: ' . json_last_error_msg()
    ], 400);
}

if (empty($data['action'])) {
    send_json_response(['success' => false, 'error' => 'Action is required.'], 400);
}

try {
    switch ($data['action']) {
        case 'create':
            // Validate required fields
            if (empty($data['transaction_date']) || empty($data['description']) || !isset($data['amount']) || empty($data['transaction_type'])) {
                send_json_response(['success' => false, 'error' => 'Missing required fields.'], 400);
            }
            
            // Validate amount is numeric and positive
            if (!is_numeric($data['amount']) || floatval($data['amount']) <= 0) {
                send_json_response(['success' => false, 'error' => 'Amount must be a positive number.'], 400);
            }
            
            // Validate transaction_type
            if (!in_array($data['transaction_type'], ['credit', 'debit'])) {
                send_json_response(['success' => false, 'error' => 'Invalid transaction type. Must be credit or debit.'], 400);
            }
            
            // Check if approval is required based on settings
            $settingsSql = "SELECT approval_threshold FROM petty_cash_float_settings ORDER BY id DESC LIMIT 1";
            $settingsStmt = $pdo->query($settingsSql);
            $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
            $approvalThreshold = $settings['approval_threshold'] ?? 50000;
            
            $requiresApproval = ($data['transaction_type'] === 'debit' && floatval($data['amount']) > $approvalThreshold);
            $approvalStatus = $requiresApproval ? 'pending' : 'approved';
            
            // Track old values for edit history
            $oldValues = [];
            
            // Insert new petty cash transaction with enhanced fields including currency
            $sql = "INSERT INTO petty_cash 
                    (user_id, transaction_date, description, beneficiary, purpose, amount, currency,
                     transaction_type, category_id, payment_method, reference, 
                     approval_status, notes) 
                    VALUES 
                    (:user_id, :transaction_date, :description, :beneficiary, :purpose, :amount, :currency,
                     :transaction_type, :category_id, :payment_method, :reference, 
                     :approval_status, :notes)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':transaction_date' => $data['transaction_date'],
                ':description' => $data['description'],
                ':beneficiary' => $data['beneficiary'] ?? null,
                ':purpose' => $data['purpose'] ?? null,
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'RWF',
                ':transaction_type' => $data['transaction_type'],
                ':category_id' => $data['category_id'] ?? null,
                ':payment_method' => $data['payment_method'] ?? null,
                ':reference' => $data['reference'] ?? null,
                ':approval_status' => $approvalStatus,
                ':notes' => $data['notes'] ?? null
            ]);
            
            $newId = $pdo->lastInsertId();
            
            // Log activity
            if (function_exists('logActivity')) {
                logActivity($_SESSION['user_id'], 'create-petty-cash', 'petty_cash', $newId, 
                           json_encode(['amount' => $data['amount'], 'type' => $data['transaction_type']]));
            }
            send_json_response([
                'success' => true,
                'message' => 'Transaction created successfully.' . ($requiresApproval ? ' Pending approval.' : ''),
                'id' => $newId,
                'requires_approval' => $requiresApproval,
                'approval_status' => $approvalStatus
            ]);
            break;
            
        case 'update':
            // Validate required fields
            if (empty($data['id'])) {
                send_json_response(['success' => false, 'error' => 'Transaction ID is required.'], 400);
            }
            
            // Validate amount if provided
            if (isset($data['amount']) && (!is_numeric($data['amount']) || floatval($data['amount']) <= 0)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Amount must be a positive number.']);
                exit;
            }
            
            // Validate transaction_type if provided
            if (isset($data['transaction_type']) && !in_array($data['transaction_type'], ['credit', 'debit'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid transaction type. Must be credit or debit.']);
                exit;
            }
            
            // Check if transaction is locked
            $checkLockSql = "SELECT is_locked FROM petty_cash WHERE id = :id";
            $checkLockStmt = $pdo->prepare($checkLockSql);
            $checkLockStmt->execute([':id' => $data['id']]);
            $lockResult = $checkLockStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lockResult && $lockResult['is_locked']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot edit locked transaction.']);
                exit;
            }
            
            // Get old values for edit history
            $oldSql = "SELECT * FROM petty_cash WHERE id = :id";
            $oldStmt = $pdo->prepare($oldSql);
            $oldStmt->execute([':id' => $data['id']]);
            $oldValues = $oldStmt->fetch(PDO::FETCH_ASSOC);
            
            // Update petty cash transaction with enhanced fields
            $sql = "UPDATE petty_cash 
                    SET transaction_date = :transaction_date,
                        description = :description,
                        beneficiary = :beneficiary,
                        purpose = :purpose,
                        amount = :amount,
                        currency = :currency,
                        transaction_type = :transaction_type,
                        category_id = :category_id,
                        payment_method = :payment_method,
                        reference = :reference,
                        notes = :notes
                    WHERE id = :id AND is_locked = 0";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                ':id' => $data['id'],
                ':transaction_date' => $data['transaction_date'],
                ':description' => $data['description'],
                ':beneficiary' => $data['beneficiary'] ?? null,
                ':purpose' => $data['purpose'] ?? null,
                ':amount' => $data['amount'],
                ':currency' => $data['currency'] ?? 'RWF',
                ':transaction_type' => $data['transaction_type'],
                ':category_id' => $data['category_id'] ?? null,
                ':payment_method' => $data['payment_method'] ?? null,
                ':reference' => $data['reference'] ?? null,
                ':notes' => $data['notes'] ?? null
            ]);
            
            // Log edit history for changed fields
            if ($oldValues) {
                $changedFields = [];
                $fieldsToTrack = ['transaction_date', 'description', 'amount', 'currency', 'transaction_type', 'category_id'];
                foreach ($fieldsToTrack as $field) {
                    if (isset($data[$field]) && $oldValues[$field] != $data[$field]) {
                        $changedFields[] = [
                            'field' => $field,
                            'old' => $oldValues[$field],
                            'new' => $data[$field]
                        ];
                        
                        // Insert edit history
                        $historySql = "INSERT INTO petty_cash_edit_history 
                                      (transaction_id, edited_by, field_name, old_value, new_value, edit_reason)
                                      VALUES (:transaction_id, :edited_by, :field_name, :old_value, :new_value, :edit_reason)";
                        $historyStmt = $pdo->prepare($historySql);
                        $historyStmt->execute([
                            ':transaction_id' => $data['id'],
                            ':edited_by' => $_SESSION['user_id'],
                            ':field_name' => $field,
                            ':old_value' => $oldValues[$field],
                            ':new_value' => $data[$field],
                            ':edit_reason' => $data['edit_reason'] ?? 'Updated'
                        ]);
                    }
                }
            }
            
            // Log activity
            if (function_exists('logActivity')) {
                logActivity($_SESSION['user_id'], 'update-petty-cash', 'petty_cash', $data['id'], 
                           json_encode(['amount' => $data['amount']]));
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Transaction updated successfully.'
            ]);
            break;
            
        case 'delete':
            // Validate required fields
            if (empty($data['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Transaction ID is required.']);
                exit;
            }
            
            // Check if transaction is locked
            $checkLockSql = "SELECT is_locked, amount, transaction_type FROM petty_cash WHERE id = :id";
            $checkLockStmt = $pdo->prepare($checkLockSql);
            $checkLockStmt->execute([':id' => $data['id']]);
            $lockResult = $checkLockStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lockResult && $lockResult['is_locked']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot delete locked transaction.']);
                exit;
            }
            
            // Delete petty cash transaction
            $sql = "DELETE FROM petty_cash WHERE id = :id AND is_locked = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $data['id']]);
            
            if ($stmt->rowCount() > 0) {
                // Log activity
                if (function_exists('logActivity') && $lockResult) {
                    logActivity($_SESSION['user_id'], 'delete-petty-cash', 'petty_cash', $data['id'], 
                               json_encode(['amount' => $lockResult['amount'], 'type' => $lockResult['transaction_type']]));
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Transaction deleted successfully.'
                ]);
            } else {
                // Check if transaction exists but is locked
                if ($lockResult && $lockResult['is_locked']) {
                    http_response_code(403);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Cannot delete: Transaction is locked.'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Transaction not found.'
                    ]);
                }
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action.']);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
