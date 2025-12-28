<?php
/**
 * fetch_petty_cash.php
 * 
 * API endpoint to retrieve petty cash transactions with filtering capabilities
 * Supports date range, transaction type, and search filters
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

require_once 'db.php';

try {
    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20; // Default 20, max 100
    $offset = ($page - 1) * $limit;
    
    // Build SQL query with filters including new fields
    $sql = "SELECT pc.id, pc.user_id, pc.transaction_date, pc.description, pc.beneficiary, pc.purpose,
                   pc.amount, pc.transaction_type, pc.category_id, pc.payment_method, pc.reference,
                   pc.receipt_path, pc.approval_status, pc.approved_by, pc.approved_at, pc.is_locked, pc.notes,
                   pc.created_at, pc.updated_at,
                   c.name as category_name, c.icon as category_icon, c.color as category_color,
                   u.username as user_name,
                   ap.username as approver_name
            FROM petty_cash pc
            LEFT JOIN petty_cash_categories c ON pc.category_id = c.id
            LEFT JOIN users u ON pc.user_id = u.id
            LEFT JOIN users ap ON pc.approved_by = ap.id
            WHERE 1=1";
    $params = [];
    
    // Date range filters
    if (!empty($_GET['from'])) {
        $sql .= " AND DATE(pc.transaction_date) >= :from";
        $params[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $sql .= " AND DATE(pc.transaction_date) <= :to";
        $params[':to'] = $_GET['to'];
    }
    
    // Transaction type filter
    if (!empty($_GET['type']) && $_GET['type'] !== 'all') {
        $sql .= " AND pc.transaction_type = :type";
        $params[':type'] = $_GET['type'];
    }
    
    // Search query (searches description, reference, payment method)
    if (!empty(trim($_GET['q']))) {
        $searchQuery = '%' . trim($_GET['q']) . '%';
        $sql .= " AND (pc.description LIKE :searchQuery1 
                      OR pc.reference LIKE :searchQuery2 
                      OR pc.payment_method LIKE :searchQuery3
                      OR DATE_FORMAT(pc.transaction_date, '%Y-%m-%d') LIKE :searchQuery4
                      OR DATE_FORMAT(pc.transaction_date, '%d/%m/%Y') LIKE :searchQuery5)";
        
        $params[':searchQuery1'] = $searchQuery;
        $params[':searchQuery2'] = $searchQuery;
        $params[':searchQuery3'] = $searchQuery;
        $params[':searchQuery4'] = $searchQuery;
        $params[':searchQuery5'] = $searchQuery;
        
        // Check if search query is numeric for amount search
        if (is_numeric(trim($_GET['q']))) {
            $sql .= " OR pc.amount = :searchQueryNumeric";
            $params[':searchQueryNumeric'] = (float)trim($_GET['q']);
        }
    }
    
    // Count total records for pagination
    $countSql = "SELECT COUNT(*) FROM petty_cash pc WHERE 1=1";
    $countParams = [];
    
    if (!empty($_GET['from'])) {
        $countSql .= " AND DATE(pc.transaction_date) >= :from";
        $countParams[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $countSql .= " AND DATE(pc.transaction_date) <= :to";
        $countParams[':to'] = $_GET['to'];
    }
    if (!empty($_GET['type']) && $_GET['type'] !== 'all') {
        $countSql .= " AND pc.transaction_type = :type";
        $countParams[':type'] = $_GET['type'];
    }
    if (!empty(trim($_GET['q']))) {
        $searchQuery = '%' . trim($_GET['q']) . '%';
        $countSql .= " AND (pc.description LIKE :searchQuery1 
                      OR pc.reference LIKE :searchQuery2 
                      OR pc.payment_method LIKE :searchQuery3
                      OR DATE_FORMAT(pc.transaction_date, '%Y-%m-%d') LIKE :searchQuery4
                      OR DATE_FORMAT(pc.transaction_date, '%d/%m/%Y') LIKE :searchQuery5)";
        
        for ($i = 1; $i <= 5; $i++) {
            $countParams[":searchQuery{$i}"] = $searchQuery;
        }
        
        if (is_numeric(trim($_GET['q']))) {
            $countSql .= " OR pc.amount = :searchQueryNumeric";
            $countParams[':searchQueryNumeric'] = (float)trim($_GET['q']);
        }
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = (int)$countStmt->fetchColumn();
    
    // Order by date descending (newest first)
    $sql .= " ORDER BY pc.transaction_date DESC, pc.id DESC";
    $sql .= " LIMIT :limit OFFSET :offset";
    
    // Execute query
    $stmt = $pdo->prepare($sql);
    
    // Bind all the regular parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters separately as integers
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit)
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
