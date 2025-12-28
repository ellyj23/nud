<?php
session_start();
header('Content-Type: application/json');

// --- Authenticate User ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

// --- Database Connection ---
require_once 'db.php';

try {
    // Determine if this is a search/filter request or an initial page load
    $isSearchOrFilter = !empty($_GET['searchQuery']) || !empty($_GET['filterDateFrom']) || !empty($_GET['filterDateTo']) || !empty($_GET['filterPaidStatus']) || !empty($_GET['filterCurrency']);

    // Pagination parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20; // Default 20, no max limit
    $offset = ($page - 1) * $limit;

    $response = ['success' => true];

    // --- PART 1: Fetch overall dashboard stats (ALWAYS fetch, regardless of filters) ---
    // Total clients count
    $totalClientsStmt = $pdo->query("SELECT COUNT(id) FROM clients");
    $totalClients = (int)$totalClientsStmt->fetchColumn();

    // Overall totals by currency (not affected by pagination)
    $summarySql = "SELECT currency, SUM(paid_amount) as total_revenue, SUM(due_amount) as outstanding_amount FROM clients GROUP BY currency";
    $summaryStmt = $pdo->query($summarySql);
    $currencyResults = $summaryStmt->fetchAll(PDO::FETCH_ASSOC);

    $currencySummaries = [];
    foreach ($currencyResults as $row) {
        $currencySummaries[$row['currency']] = [
            'total_revenue' => (float)$row['total_revenue'],
            'outstanding_amount' => (float)$row['outstanding_amount']
        ];
    }
    foreach (['RWF', 'USD', 'EUR'] as $primaryCurrency) {
        if (!isset($currencySummaries[$primaryCurrency])) {
            $currencySummaries[$primaryCurrency] = ['total_revenue' => 0, 'outstanding_amount' => 0];
        }
    }
    
    // Always include stats in response
    $response['stats'] = [
        'currencySummaries' => $currencySummaries,
        'totalClients' => $totalClients,
        'revenueChange' => (rand(0, 1) ? 1 : -1) * (rand(10, 250) / 10),
        'outstandingChange' => (rand(0, 1) ? 1 : -1) * (rand(10, 150) / 10),
        'newClients' => rand(5, 20)
    ];

    // --- PART 2: Fetch client list (either full or filtered) ---
    $clientsSql = "SELECT *, 
           (CASE 
               WHEN paid_amount >= amount THEN 'PAID'
               WHEN paid_amount > 0 THEN 'PARTIALLY PAID'
               ELSE 'NOT PAID'
           END) AS status 
        FROM clients";

    $params = [];
    $where_clauses = [];

    // Check if search or any filter is active
    $isSearchActive = !empty($_GET['searchQuery']);
    $isFilterActive = !empty($_GET['filterDateFrom']) || !empty($_GET['filterDateTo']) || 
                      !empty($_GET['filterPaidStatus']) || !empty($_GET['filterCurrency']);

    // **FIXED**: Use unique named placeholders for the search query to prevent "Invalid parameter number" error.
    if ($isSearchActive) {
        $searchQuery = '%' . trim($_GET['searchQuery']) . '%';
        // Search by reg_no, client_name, Responsible, and TIN
        $where_clauses[] = "(reg_no LIKE :searchQuery1 OR client_name LIKE :searchQuery2 OR Responsible LIKE :searchQuery3 OR TIN LIKE :searchQuery4)";
        $params[':searchQuery1'] = $searchQuery;
        $params[':searchQuery2'] = $searchQuery;
        $params[':searchQuery3'] = $searchQuery;
        $params[':searchQuery4'] = $searchQuery;
    }
    
    // 24-hour delay filter for JOSEPH records during search/filter
    // Hide JOSEPH records created less than 24 hours ago when searching or filtering
    // Use date column as fallback since created_at may not exist
    // Only hide JOSEPH records if they were added today (within 24 hours based on date field)
    if ($isSearchActive || $isFilterActive) {
        $where_clauses[] = "(UPPER(Responsible) NOT LIKE '%JOSEPH%' OR date < CURDATE())";
    }
    
    if (!empty($_GET['filterDateFrom'])) {
        $where_clauses[] = "date >= :dateFrom";
        $params[':dateFrom'] = $_GET['filterDateFrom'];
    }
    if (!empty($_GET['filterDateTo'])) {
        $where_clauses[] = "date <= :dateTo";
        $params[':dateTo'] = $_GET['filterDateTo'];
    }
    if (!empty($_GET['filterPaidStatus'])) {
        switch ($_GET['filterPaidStatus']) {
            case 'PAID': $where_clauses[] = "paid_amount >= amount"; break;
            case 'PARTIALLY PAID': $where_clauses[] = "paid_amount > 0 AND paid_amount < amount"; break;
            case 'NOT PAID': $where_clauses[] = "paid_amount = 0"; break;
        }
    }
    if (!empty($_GET['filterCurrency'])) {
        $where_clauses[] = "currency = :currency";
        $params[':currency'] = $_GET['filterCurrency'];
    }

    if (!empty($where_clauses)) {
        $clientsSql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // Count total records for pagination
    $countSql = "SELECT COUNT(*) FROM clients";
    if (!empty($where_clauses)) {
        $countSql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();

    $clientsSql .= " ORDER BY date DESC, id DESC";
    $clientsSql .= " LIMIT :limit OFFSET :offset";

    $clientsStmt = $pdo->prepare($clientsSql);
    
    // Bind all the regular parameters
    foreach ($params as $key => $value) {
        $clientsStmt->bindValue($key, $value);
    }
    
    // Bind pagination parameters separately as integers
    $clientsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $clientsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $clientsStmt->execute();
    $clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['clients'] = $clients;
    
    // Add pagination information
    $response['pagination'] = [
        'current_page' => $page,
        'per_page' => $limit,
        'total_records' => $totalRecords,
        'total_pages' => ceil($totalRecords / $limit)
    ];

    // --- PART 3: Return the final JSON response ---
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'A database error occurred.',
        'details' => $e->getMessage() // For debugging
    ]);
}
?>
