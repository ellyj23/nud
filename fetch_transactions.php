<?php
/**
 * fetch_transactions.php
 * 
 * Enhanced transaction fetching API with comprehensive search functionality.
 * 
 * ENHANCEMENTS:
 * - Multiple date format search support (YYYY-MM-DD, DD/MM/YYYY, MM/DD/YYYY, YYYY/MM/DD)
 * - All transaction fields searchable (number, reference, note, status, payment_method, type, amount)
 * - Works with existing database structure without requiring additional tables
 * - Maintains existing functionality while expanding search capabilities
 * - Uses prepared statements for security
 * FIXED: Removed JOINs with wp_ea_contacts and wp_ea_categories tables to fix database compatibility
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

require_once 'db.php';

try {
    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20; // Default 20, no max limit
    $offset = ($page - 1) * $limit;
    
    // Simple query without JOINs - only uses wp_ea_transactions table
    $sql = "SELECT t.id, t.type, t.number, t.payment_date, t.amount, t.currency, t.reference, t.note, t.status, t.payment_method, t.refundable
            FROM wp_ea_transactions t
            WHERE 1=1";
    $params = [];
    
    // Build WHERE clauses based on filters
    if (!empty($_GET['from'])) {
        $sql .= " AND DATE(t.payment_date) >= :from";
        $params[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $sql .= " AND DATE(t.payment_date) <= :to";
        $params[':to'] = $_GET['to'];
    }
    if (!empty($_GET['type']) && $_GET['type'] !== 'all') {
        $sql .= " AND t.type = :type";
        $params[':type'] = $_GET['type'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
        $sql .= " AND t.status = :status";
        $params[':status'] = $_GET['status'];
    }
    if (!empty($_GET['currency']) && $_GET['currency'] !== 'all') {
        $sql .= " AND t.currency = :currency";
        $params[':currency'] = $_GET['currency'];
    }
    
    if (!empty(trim($_GET['q']))) {
        $searchQuery = '%' . trim($_GET['q']) . '%';
        // Search within transaction table fields and formatted dates
        $sql .= " AND (t.number LIKE :searchQuery1 
                      OR t.reference LIKE :searchQuery2 
                      OR t.note LIKE :searchQuery3 
                      OR t.status LIKE :searchQuery4 
                      OR t.payment_method LIKE :searchQuery5
                      OR t.type LIKE :searchQuery6
                      OR DATE_FORMAT(t.payment_date, '%Y-%m-%d') LIKE :searchQuery7
                      OR DATE_FORMAT(t.payment_date, '%d/%m/%Y') LIKE :searchQuery8
                      OR DATE_FORMAT(t.payment_date, '%m/%d/%Y') LIKE :searchQuery9
                      OR DATE_FORMAT(t.payment_date, '%Y/%m/%d') LIKE :searchQuery10)";
        
        $params[':searchQuery1'] = $searchQuery;
        $params[':searchQuery2'] = $searchQuery;
        $params[':searchQuery3'] = $searchQuery;
        $params[':searchQuery4'] = $searchQuery;
        $params[':searchQuery5'] = $searchQuery;
        $params[':searchQuery6'] = $searchQuery;
        $params[':searchQuery7'] = $searchQuery;
        $params[':searchQuery8'] = $searchQuery;
        $params[':searchQuery9'] = $searchQuery;
        $params[':searchQuery10'] = $searchQuery;
        
        // Check if search query is numeric for amount search
        if (is_numeric(trim($_GET['q']))) {
            $sql .= " OR t.amount = :searchQueryNumeric";
            $params[':searchQueryNumeric'] = (float)trim($_GET['q']);
        }
    }
    
    // Count total records for pagination
    $countSql = "SELECT COUNT(*) FROM wp_ea_transactions t WHERE 1=1";
    $countParams = [];
    
    if (!empty($_GET['from'])) {
        $countSql .= " AND DATE(t.payment_date) >= :from";
        $countParams[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $countSql .= " AND DATE(t.payment_date) <= :to";
        $countParams[':to'] = $_GET['to'];
    }
    if (!empty($_GET['type']) && $_GET['type'] !== 'all') {
        $countSql .= " AND t.type = :type";
        $countParams[':type'] = $_GET['type'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
        $countSql .= " AND t.status = :status";
        $countParams[':status'] = $_GET['status'];
    }
    if (!empty($_GET['currency']) && $_GET['currency'] !== 'all') {
        $countSql .= " AND t.currency = :currency";
        $countParams[':currency'] = $_GET['currency'];
    }
    if (!empty(trim($_GET['q']))) {
        $searchQuery = '%' . trim($_GET['q']) . '%';
        $countSql .= " AND (t.number LIKE :searchQuery1 
                      OR t.reference LIKE :searchQuery2 
                      OR t.note LIKE :searchQuery3 
                      OR t.status LIKE :searchQuery4 
                      OR t.payment_method LIKE :searchQuery5
                      OR t.type LIKE :searchQuery6
                      OR DATE_FORMAT(t.payment_date, '%Y-%m-%d') LIKE :searchQuery7
                      OR DATE_FORMAT(t.payment_date, '%d/%m/%Y') LIKE :searchQuery8
                      OR DATE_FORMAT(t.payment_date, '%m/%d/%Y') LIKE :searchQuery9
                      OR DATE_FORMAT(t.payment_date, '%Y/%m/%d') LIKE :searchQuery10)";
        
        for ($i = 1; $i <= 10; $i++) {
            $countParams[":searchQuery{$i}"] = $searchQuery;
        }
        
        if (is_numeric(trim($_GET['q']))) {
            $countSql .= " OR t.amount = :searchQueryNumeric";
            $countParams[':searchQueryNumeric'] = (float)trim($_GET['q']);
        }
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = (int)$countStmt->fetchColumn();
    
    $sql .= " ORDER BY t.payment_date DESC, t.id DESC";
    $sql .= " LIMIT :limit OFFSET :offset";
    
    // Execute the query
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
    
    // Calculate overall totals (not affected by pagination)
    $overallTotalsSql = "SELECT 
        SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as total_income,
        SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
        currency
        FROM wp_ea_transactions
        WHERE 1=1";
    
    $overallTotalsParams = [];
    
    // Apply the same filters as the main query (excluding pagination)
    if (!empty($_GET['from'])) {
        $overallTotalsSql .= " AND DATE(payment_date) >= :from";
        $overallTotalsParams[':from'] = $_GET['from'];
    }
    if (!empty($_GET['to'])) {
        $overallTotalsSql .= " AND DATE(payment_date) <= :to";
        $overallTotalsParams[':to'] = $_GET['to'];
    }
    if (!empty($_GET['type']) && $_GET['type'] !== 'all') {
        $overallTotalsSql .= " AND type = :type";
        $overallTotalsParams[':type'] = $_GET['type'];
    }
    if (!empty($_GET['status']) && $_GET['status'] !== 'all') {
        $overallTotalsSql .= " AND status = :status";
        $overallTotalsParams[':status'] = $_GET['status'];
    }
    if (!empty($_GET['currency']) && $_GET['currency'] !== 'all') {
        $overallTotalsSql .= " AND currency = :currency";
        $overallTotalsParams[':currency'] = $_GET['currency'];
    }
    
    $overallTotalsSql .= " GROUP BY currency";
    
    $overallTotalsStmt = $pdo->prepare($overallTotalsSql);
    $overallTotalsStmt->execute($overallTotalsParams);
    $overallTotals = $overallTotalsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $transactions,
        'overall_totals' => $overallTotals,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit)
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>