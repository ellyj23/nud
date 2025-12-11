<?php
/**
 * API Endpoint: Clients
 * RESTful API for client management
 */

header('Content-Type: application/json');

// Allow CORS for API access (configure appropriately for production)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../../db.php';

// Simple authentication check (should be replaced with JWT in production)
session_start();

// Check if user is authenticated
$authenticated = false;

// Check for Bearer token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? (function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? '') : '');
if (!empty($authHeader)) {
    $token = str_replace('Bearer ', '', $authHeader);
    
    // Verify token (simplified - should use JWT verification)
    try {
        $stmt = $pdo->prepare("
            SELECT user_id FROM api_tokens 
            WHERE token = ? 
            AND (expires_at IS NULL OR expires_at > NOW())
            AND revoked_at IS NULL
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tokenData) {
            $authenticated = true;
            $_SESSION['user_id'] = $tokenData['user_id'];
            
            // Update last used timestamp
            $stmt = $pdo->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE token = ?");
            $stmt->execute([$token]);
        }
    } catch (PDOException $e) {
        // Token verification failed
    }
}

// Fallback to session authentication
if (!$authenticated && isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $authenticated = true;
}

if (!$authenticated) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Invalid or missing authentication token'
    ]);
    exit;
}

// Check database connection
if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
    exit;
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
            
        case 'POST':
            handlePost($pdo);
            break;
            
        case 'PUT':
            handlePut($pdo);
            break;
            
        case 'DELETE':
            handleDelete($pdo);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

/**
 * Handle GET requests
 */
function handleGet($pdo) {
    // Check if requesting specific client
    $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
    $clientId = end($pathParts);
    
    if (is_numeric($clientId)) {
        // Get specific client
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            echo json_encode([
                'success' => true,
                'data' => $client
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Client not found'
            ]);
        }
    } else {
        // Get list of clients with pagination
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(100, max(1, intval($_GET['limit']))) : 50;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        
        // Build query
        $query = "SELECT * FROM clients";
        $params = [];
        
        if (!empty($search)) {
            $query .= " WHERE name LIKE ? OR email LIKE ?";
            $searchTerm = "%{$search}%";
            $params = [$searchTerm, $searchTerm];
        }
        
        // Get total count
        $countStmt = $pdo->prepare(str_replace('*', 'COUNT(*) as total', $query));
        $countStmt->execute($params);
        $totalItems = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $clients,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalItems / $limit),
                'total_items' => $totalItems,
                'per_page' => $limit
            ]
        ]);
    }
}

/**
 * Handle POST requests (create new client)
 */
function handlePost($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Client name is required'
        ]);
        return;
    }
    
    // Insert new client
    $stmt = $pdo->prepare("
        INSERT INTO clients (name, email, phone, address, tin, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $data['name'],
        $data['email'] ?? null,
        $data['phone'] ?? null,
        $data['address'] ?? null,
        $data['tin'] ?? null
    ]);
    
    $clientId = $pdo->lastInsertId();
    
    // Fetch and return the created client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'data' => $client,
        'message' => 'Client created successfully'
    ]);
}

/**
 * Handle PUT requests (update client)
 */
function handlePut($pdo) {
    $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
    $clientId = end($pathParts);
    
    if (!is_numeric($clientId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid client ID'
        ]);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Build update query dynamically
    $fields = [];
    $params = [];
    
    foreach (['name', 'email', 'phone', 'address', 'tin'] as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }
    
    if (empty($fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No fields to update'
        ]);
        return;
    }
    
    $params[] = $clientId;
    
    $stmt = $pdo->prepare("
        UPDATE clients 
        SET " . implode(', ', $fields) . "
        WHERE id = ?
    ");
    
    $stmt->execute($params);
    
    // Fetch and return updated client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        echo json_encode([
            'success' => true,
            'data' => $client,
            'message' => 'Client updated successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Client not found'
        ]);
    }
}

/**
 * Handle DELETE requests
 */
function handleDelete($pdo) {
    $pathParts = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
    $clientId = end($pathParts);
    
    if (!is_numeric($clientId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid client ID'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Client deleted successfully'
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Client not found'
        ]);
    }
}
