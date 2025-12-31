<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied']);
    exit;
}

require_once 'db.php'; // CORRECTED FILENAME
require_once 'rbac.php';
require_once 'activity_logger.php';

// Check permission to delete clients
if (!userHasPermission($_SESSION['user_id'], 'delete-client')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access Denied. You do not have permission to delete clients.']);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid client ID.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT client_name, reg_no FROM clients WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$client) throw new Exception("Client with ID $id not found.");
    $details = "Deleted client: {$client['client_name']} (Reg No: {$client['reg_no']})";

    $historySql = "INSERT INTO client_history (client_id, user_name, action, details) VALUES (:client_id, :user_name, :action, :details)";
    $historyStmt = $pdo->prepare($historySql);
    $historyStmt->execute([
        ':client_id' => $id,
        ':user_name' => $_SESSION['username'] ?? 'System',
        ':action' => 'DELETE',
        ':details' => $details
    ]);

    $deleteStmt = $pdo->prepare("DELETE FROM clients WHERE id = :id");
    $deleteStmt->execute([':id' => $id]);

    $pdo->commit();
    
    // Log activity
    logActivity($_SESSION['user_id'] ?? null, 'delete-client', 'clients', $id, [
        'client_name' => $client['client_name'],
        'reg_no' => $client['reg_no']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Client deleted successfully!']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>