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
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Configuration error: PDO connection object not found in db.php.']);
    exit;
}

// Check permission to edit clients
if (!userHasPermission($_SESSION['user_id'], 'edit-client')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied. You do not have permission to edit clients.']);
    exit;
}

// Get POST data using modern, safe methods
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

// Validate amount - allow 0 but not false
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
if ($amount === false && isset($_POST['amount'])) {
    // Try to clean the input and parse again
    $amountCleaned = preg_replace('/[^0-9.]/', '', $_POST['amount']);
    // Ensure only one decimal point
    if (substr_count($amountCleaned, '.') > 1) {
        $parts = explode('.', $amountCleaned);
        $amountCleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
    }
    $amount = filter_var($amountCleaned, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}
if ($amount === false) {
    $amount = 0;
}

// Validate paid_amount - allow 0 but not false
$paid_amount = filter_input(INPUT_POST, 'paid_amount', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
if ($paid_amount === false && isset($_POST['paid_amount'])) {
    // Try to clean the input and parse again
    $paidCleaned = preg_replace('/[^0-9.]/', '', $_POST['paid_amount']);
    // Ensure only one decimal point
    if (substr_count($paidCleaned, '.') > 1) {
        $parts = explode('.', $paidCleaned);
        $paidCleaned = $parts[0] . '.' . implode('', array_slice($parts, 1));
    }
    $paid_amount = filter_var($paidCleaned, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}
if ($paid_amount === false) {
    $paid_amount = 0;
}

$newData = [
    'reg_no' => isset($_POST['reg_no']) ? htmlspecialchars(trim($_POST['reg_no']), ENT_QUOTES, 'UTF-8') : '',
    'client_name' => isset($_POST['client_name']) ? htmlspecialchars(trim($_POST['client_name']), ENT_QUOTES, 'UTF-8') : '',
    'date' => isset($_POST['date']) ? trim($_POST['date']) : '',
    'Responsible' => isset($_POST['Responsible']) ? htmlspecialchars(trim($_POST['Responsible']), ENT_QUOTES, 'UTF-8') : '',
    'TIN' => isset($_POST['TIN']) ? htmlspecialchars(trim($_POST['TIN']), ENT_QUOTES, 'UTF-8') : '',
    'service' => isset($_POST['service']) ? htmlspecialchars(trim($_POST['service']), ENT_QUOTES, 'UTF-8') : '',
    'currency' => isset($_POST['currency']) ? htmlspecialchars(trim($_POST['currency']), ENT_QUOTES, 'UTF-8') : '',
    'amount' => $amount,
    'paid_amount' => $paid_amount
];

if (!$id || empty($newData['client_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input data. Client ID and Name are required.']);
    exit;
}

// Validate TIN if provided - must be numeric and max 9 digits
if (!empty($newData['TIN']) && (!ctype_digit($newData['TIN']) || strlen($newData['TIN']) > 9)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'TIN must be numeric and up to 9 digits.']);
    exit;
}

// Recalculate due amount and status
$newData['due_amount'] = $newData['amount'] - $newData['paid_amount'];
$newData['status'] = 'NOT PAID';
if ($newData['amount'] > 0 && $newData['paid_amount'] >= $newData['amount']) {
    $newData['status'] = 'PAID';
    $newData['due_amount'] = 0;
} elseif ($newData['paid_amount'] > 0) {
    $newData['status'] = 'PARTIALLY PAID';
}


try {
    $pdo->beginTransaction();

    // Get the old data for comparison in the history log
    $oldStmt = $pdo->prepare("SELECT * FROM clients WHERE id = :id");
    $oldStmt->execute([':id' => $id]);
    $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
    if (!$oldData) {
        throw new Exception("Client with ID $id not found.");
    }

    // Check for duplicate reg_no considering year and service type
    // Same reg_no is allowed if the year (from date) or service type is different
    // Exclude current record from duplicate check
    if (!empty($newData['reg_no'])) {
        $checkSql = "SELECT COUNT(*) FROM clients 
                     WHERE reg_no = :reg_no 
                     AND YEAR(date) = YEAR(:date) 
                     AND service = :service 
                     AND id != :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([
            ':reg_no' => $newData['reg_no'],
            ':date' => $newData['date'],
            ':service' => $newData['service'],
            ':id' => $id
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

    $sql = "UPDATE clients SET reg_no=:reg_no, client_name=:client_name, date=:date, Responsible=:Responsible, TIN=:TIN, service=:service, amount=:amount, currency=:currency, paid_amount=:paid_amount, due_amount=:due_amount, status=:status WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $updateData = array_merge($newData, ['id' => $id]);
    // Convert empty TIN to null
    $updateData['TIN'] = !empty($updateData['TIN']) ? $updateData['TIN'] : null;
    $stmt->execute($updateData);

    // Generate a detailed history log
    $changes = [];
    $editableFields = ['reg_no', 'client_name', 'date', 'Responsible', 'TIN', 'service', 'amount', 'currency', 'paid_amount'];
    foreach ($editableFields as $key) {
        // Use string casting to handle type differences (e.g., '50.00' vs 50)
        if (isset($oldData[$key]) && isset($newData[$key]) && (string)$oldData[$key] !== (string)$newData[$key]) {
            $changes[] = "Changed '$key' from '{$oldData[$key]}' to '{$newData[$key]}'";
        }
    }
    if ($oldData['status'] !== $newData['status']) {
        $changes[] = "Status changed from '{$oldData['status']}' to '{$newData['status']}'";
    }
    $details = empty($changes) ? 'Record was resaved with no changes.' : implode('; ', $changes);

    $historySql = "INSERT INTO client_history (client_id, user_name, action, details) VALUES (:client_id, :user_name, :action, :details)";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([
        ':client_id' => $id,
        ':user_name' => $_SESSION['username'] ?? 'System',
        ':action' => 'UPDATE',
        ':details' => $details
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Client updated successfully!']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    $errorInfo = ($e instanceof PDOException) ? $e->errorInfo : null;
    echo json_encode([
        'success' => false,
        'error' => 'Database error during update.',
        'details' => $e->getMessage(),
        'sql_error_code' => $errorInfo[1] ?? null,
        'sql_error_message' => $errorInfo[2] ?? null,
    ]);
}
?>