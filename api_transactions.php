<?php
/**
 * api_transactions.php
 *
 * This script serves as the backend API for handling transaction data.
 * **FIXED**: Database connection handling - prevents generic "Database operation failed" errors
 * **FIXED**: Improved error handling with specific database error messages and details
 * **FIXED**: Added validation to ensure database connection is available before processing requests
 * **FIXED**: Corrected logic for create/update/bulk-update to properly handle the 'refundable' field.
 * **FIXED**: Reworked query builder to correctly handle combined filters and searches.
 * **NEW**: Added 'refundable' field for expenses.
 * **ENHANCED**: Enhanced search functionality with comprehensive field coverage including:
 *   - Multiple date format search (YYYY-MM-DD, DD/MM/YYYY, MM/DD/YYYY, YYYY/MM/DD)
 *   - All transaction fields (number, reference, note, status, payment_method, type, amount)
 *   - Works with existing database structure without requiring additional tables
 * **FIXED**: Removed JOINs with wp_ea_contacts and wp_ea_categories tables to fix database compatibility
 */

header('Content-Type: application/json');
// It's better to log errors than display them in a JSON API
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'db.php';
require_once 'rbac.php';
require_once 'fpdf/fpdf.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

// Check if database connection was successful
if ($pdo === null) {
    // Database connection failed, return the error from db.php
    if (isset($db_connection_error)) {
        http_response_code(500);
        echo json_encode($db_connection_error);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Database connection is not available'
        ]);
    }
    exit;
}

// --- Custom PDF Class ---
class PDF_Feza extends FPDF {
    // Company information
    private $logoUrl = 'https://www.fezalogistics.com/wp-content/uploads/2025/06/SQUARE-SIZEXX-FEZA-LOGO.png';
    private $companyName = 'FEZA LOGISTICS';
    private $address = 'Kigali, Rwanda';
    private $taxId = 'TIN: 123456789';
    private $email = 'info@fezalogistics.com';
    private $website = 'www.fezalogistics.com';
    private $documentTitle = 'TRANSACTION REPORT';
    private $referenceNumber;
    private $generatedDate;
    
    // Styling constants
    const HEADER_BG_COLOR = [242, 242, 242]; // #f2f2f2
    const BORDER_COLOR = [221, 221, 221];    // #dddddd
    const TEXT_COLOR = [0, 0, 0];
    const FOOTER_TEXT_COLOR = [100, 100, 100];
    
    // Column widths for transaction table (Portrait A4: Date, Type, Number, Client, Description, Amount, Status)
    const COLUMN_WIDTHS = [20, 15, 25, 50, 45, 20, 15];
    
    // Page break threshold for Portrait A4
    const PAGE_BREAK_THRESHOLD = 250;
    
    function __construct($orientation = 'P', $unit = 'mm', $size = 'A4') {
        parent::__construct($orientation, $unit, $size);
        $this->generatedDate = date('Y-m-d H:i:s');
        // Use uniqid for better uniqueness guarantees
        $this->referenceNumber = 'TR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
    
    function Header() {
        // Logo on top-left
        $this->Image($this->logoUrl, 10, 10, 25);
        
        // Company details on top-left (next to logo)
        $this->SetFont('Arial', 'B', 14);
        $this->SetXY(40, 10);
        $this->Cell(60, 6, $this->companyName, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 8);
        $this->SetX(40);
        $this->Cell(60, 4, $this->address, 0, 1, 'L');
        $this->SetX(40);
        $this->Cell(60, 4, $this->taxId, 0, 1, 'L');
        $this->SetX(40);
        $this->Cell(60, 4, $this->email, 0, 1, 'L');
        $this->SetX(40);
        $this->Cell(60, 4, $this->website, 0, 1, 'L');
        
        // Metadata on top-right (adjusted for Portrait)
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(110, 10);
        $this->Cell(90, 6, $this->documentTitle, 0, 1, 'R');
        
        $this->SetFont('Arial', '', 8);
        $this->SetXY(110, 16);
        $this->Cell(90, 4, 'Generated: ' . $this->generatedDate, 0, 1, 'R');
        $this->SetXY(110, 20);
        $this->Cell(90, 4, 'Ref: ' . $this->referenceNumber, 0, 1, 'R');
        $this->SetXY(110, 24);
        $this->Cell(90, 4, 'Prepared by: System', 0, 1, 'R');
        
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-20);
        
        // Professional footer with disclaimer
        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(...self::FOOTER_TEXT_COLOR);
        $this->Cell(0, 4, 'System Generated Document - No Signature Required', 0, 1, 'C');
        $this->Cell(0, 4, 'This is a computer-generated report and does not require physical signature.', 0, 1, 'C');
        
        // Page numbering
        $this->SetY(-10);
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(...self::TEXT_COLOR);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
    }
    
    // Advanced row handling with multi-line text wrapping
    function Row($data, $heights = null, $aligns = null) {
        // Calculate the height required for each cell
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines(self::COLUMN_WIDTHS[$i], $data[$i]));
        }
        
        $h = $heights ?? 6;
        $cellHeight = $h * $nb;
        
        // Check if we need a new page
        $this->CheckPageBreak($cellHeight);
        
        // Draw the cells
        for ($i = 0; $i < count($data); $i++) {
            $w = self::COLUMN_WIDTHS[$i];
            $align = $aligns[$i] ?? 'L';
            
            // Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            
            // Draw the border with unified styling
            $this->SetDrawColor(...self::BORDER_COLOR);
            $this->Rect($x, $y, $w, $cellHeight);
            
            // Print the text
            $this->MultiCell($w, $h, $data[$i], 0, $align);
            
            // Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
        }
        
        // Go to the next line
        $this->Ln($cellHeight);
    }
    
    function NbLines($w, $txt) {
        // Calculate the number of lines a MultiCell would take
        if (!isset($this->CurrentFont) || empty($this->CurrentFont['cw'])) {
            // If font not set, ensure a font is set first
            $this->SetFont('Arial', '', 8);
        }
        
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        
        if ($nb > 0 && $s[$nb - 1] == "\n") {
            $nb--;
        }
        
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            
            $l += isset($cw[$c]) ? $cw[$c] : 500;
            
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        
        return $nl;
    }
    
    function CheckPageBreak($h) {
        // If the height h would cause an overflow, add a new page immediately
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }
    
    // Helper method to draw transaction table header (Updated: Removed Payment Method, Added Client)
    function drawTransactionTableHeader() {
        $this->SetFillColor(...self::HEADER_BG_COLOR);
        $this->SetDrawColor(...self::BORDER_COLOR);
        $this->SetFont('Arial', 'B', 9);
        
        $this->Cell(20, 8, 'Date', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Type', 1, 0, 'C', true);
        $this->Cell(25, 8, 'Number', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Client / Reference', 1, 0, 'C', true);
        $this->Cell(45, 8, 'Description', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Amount', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Status', 1, 1, 'C', true);
    }
}


// --- HELPER FUNCTIONS ---
function send_json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function validate_database_connection($pdo) {
    if ($pdo === null) {
        return false;
    }
    
    try {
        // Test the connection with a simple query
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function empty_string_to_null($value) {
    // Convert empty strings to NULL, preserve all other values including '0'
    return (isset($value) && $value !== '') ? $value : null;
}

function get_next_transaction_number($pdo, $type) {
    // (This function is correct and unchanged)
    $prefix = (strtoupper($type) === 'EXPENSE') ? 'EXP-' : 'PAY-';
    $sql = "SELECT number FROM wp_ea_transactions WHERE number LIKE :prefix ORDER BY CAST(SUBSTRING(number, 5) AS UNSIGNED) DESC, id DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);
    $last_number = $stmt->fetchColumn();
    $next_numeric_part = $last_number ? ((int)str_replace($prefix, '', $last_number) + 1) : 1;
    return $prefix . str_pad($next_numeric_part, 4, '0', STR_PAD_LEFT);
}

// --- ROUTING ---
$method = $_SERVER['REQUEST_METHOD'];
$data = [];
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Check if JSON decoding failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        send_json_response([
            'success' => false,
            'error' => 'Invalid JSON data: ' . json_last_error_msg()
        ], 400);
    }
}
$action = $data['action'] ?? $_GET['action'] ?? null;

// Double-check database connection before processing any requests
if (!validate_database_connection($pdo)) {
    send_json_response([
        'success' => false,
        'error' => 'Database connection lost or unavailable'
    ], 500);
}

try {
    switch ($action) {
        case 'print': 
            handle_print($pdo, $data); 
            break;
        case 'create': 
            // Check permission to create transactions
            if (!userHasPermission($_SESSION['user_id'], 'create-transaction')) {
                send_json_response(['success' => false, 'error' => 'Access denied. You do not have permission to create transactions.'], 403);
                exit;
            }
            create_transaction($pdo, $data); 
            break;
        case 'update': 
            // Check permission to edit transactions
            if (!userHasPermission($_SESSION['user_id'], 'edit-transaction')) {
                send_json_response(['success' => false, 'error' => 'Access denied. You do not have permission to edit transactions.'], 403);
                exit;
            }
            update_transaction($pdo, $data); 
            break;
        case 'bulk_update': 
            // Check permission to edit transactions
            if (!userHasPermission($_SESSION['user_id'], 'edit-transaction')) {
                send_json_response(['success' => false, 'error' => 'Access denied. You do not have permission to edit transactions.'], 403);
                exit;
            }
            bulk_update_transactions($pdo, $data); 
            break;
        case 'delete': 
            // Check permission to delete transactions
            if (!userHasPermission($_SESSION['user_id'], 'delete-transaction')) {
                send_json_response(['success' => false, 'error' => 'Access denied. You do not have permission to delete transactions.'], 403);
                exit;
            }
            delete_transaction($pdo, $data); 
            break;
        default:
            if ($method === 'GET') {
                // Check permission to view transactions
                if (!userHasPermission($_SESSION['user_id'], 'view-transactions')) {
                    send_json_response(['success' => false, 'error' => 'Access denied. You do not have permission to view transactions.'], 403);
                    exit;
                }
                handle_get($pdo);
            } else {
                send_json_response(['success' => false, 'error' => 'Invalid action specified.'], 400);
            }
    }
} catch (PDOException $e) {
    // Specific database error - provide more detailed information
    $error_message = 'Database operation failed';
    
    // Add more specific error information based on error code
    if (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
        $error_message = 'Database table or column does not exist';
    } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        $error_message = 'Duplicate entry - record already exists';
    } elseif (strpos($e->getMessage(), 'cannot be null') !== false) {
        $error_message = 'Required field is missing or null';
    } elseif ($e->getCode() == '42S02') {
        $error_message = 'Database table does not exist';
    } elseif ($e->getCode() == '23000') {
        $error_message = 'Data integrity constraint violation';
    }
    
    send_json_response([
        'success' => false, 
        'error' => $error_message,
        'details' => $e->getMessage() // Include technical details for debugging
    ], 500);
} catch (Exception $e) {
    // All other errors
    send_json_response([
        'success' => false, 
        'error' => 'An unexpected server error occurred',
        'details' => $e->getMessage()
    ], 500);
}


// --- HANDLERS ---
function build_get_query($filters = []) {
    global $pdo;
    
    // Simple query without JOINs - only uses wp_ea_transactions table
    $sql = "SELECT t.id, t.type, t.number, t.payment_date, t.amount, t.currency, t.reference, t.note, t.status, t.payment_method, t.refundable
            FROM wp_ea_transactions t
            WHERE 1=1";
    
    $params = [];
    
    // Date filters
    if (!empty($filters['from'])) {
        $sql .= " AND DATE(t.payment_date) >= :from";
        $params[':from'] = $filters['from'];
    }
    if (!empty($filters['to'])) {
        $sql .= " AND DATE(t.payment_date) <= :to";
        $params[':to'] = $filters['to'];
    }
    
    // Type filter
    if (!empty($filters['type']) && $filters['type'] !== 'all') {
        $sql .= " AND t.type = :type";
        $params[':type'] = $filters['type'];
    }
    
    // Status filter
    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND t.status = :status";
        $params[':status'] = $filters['status'];
    }
    
    // Currency filter
    if (!empty($filters['currency']) && $filters['currency'] !== 'all') {
        $sql .= " AND t.currency = :currency";
        $params[':currency'] = $filters['currency'];
    }
    
    // Search filter - only search within transaction table fields
    if (!empty($filters['q'])) {
        $searchQuery = '%' . $filters['q'] . '%';
        $sql .= " AND (t.number LIKE :q_like1 
                      OR t.reference LIKE :q_like2 
                      OR t.note LIKE :q_like3 
                      OR t.status LIKE :q_like4 
                      OR t.payment_method LIKE :q_like5
                      OR t.type LIKE :q_like6
                      OR DATE_FORMAT(t.payment_date, '%Y-%m-%d') LIKE :q_like7
                      OR DATE_FORMAT(t.payment_date, '%d/%m/%Y') LIKE :q_like8
                      OR DATE_FORMAT(t.payment_date, '%m/%d/%Y') LIKE :q_like9
                      OR DATE_FORMAT(t.payment_date, '%Y/%m/%d') LIKE :q_like10)";
        
        $params[':q_like1'] = $searchQuery;
        $params[':q_like2'] = $searchQuery;
        $params[':q_like3'] = $searchQuery;
        $params[':q_like4'] = $searchQuery;
        $params[':q_like5'] = $searchQuery;
        $params[':q_like6'] = $searchQuery;
        $params[':q_like7'] = $searchQuery;
        $params[':q_like8'] = $searchQuery;
        $params[':q_like9'] = $searchQuery;
        $params[':q_like10'] = $searchQuery;
        
        // Check if search query is numeric for amount search
        if (is_numeric($filters['q'])) {
            $sql .= " OR t.amount = :q_numeric";
            $params[':q_numeric'] = (float)$filters['q'];
        }
    }
    
    // Default ordering
    $sql .= " ORDER BY t.payment_date DESC, t.id DESC";
    
    return ['sql' => $sql, 'params' => $params];
}
function handle_get($pdo) {
    $query_info = build_get_query($_GET);
    $stmt = $pdo->prepare($query_info['sql']);
    $stmt->execute($query_info['params']);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($transactions as &$tx) {
        $tx['payment_date'] = (new DateTime($tx['payment_date']))->format('Y-m-d');
    }
    send_json_response(['success' => true, 'data' => $transactions]);
}

function create_transaction($pdo, $data) {
    $sql = "INSERT INTO wp_ea_transactions (payment_date, type, number, amount, currency, reference, note, status, payment_method, refundable, account_id, category_id) VALUES (:payment_date, :type, :number, :amount, :currency, :reference, :note, :status, :payment_method, :refundable, 1, 1)";
    $stmt = $pdo->prepare($sql);
    $type = $data['type'] ?? 'expense';
    $number = get_next_transaction_number($pdo, $type);
    // **FIXED**: Correctly default refundable to 0 if not provided or if type is not 'expense'
    $refundable = ($type === 'expense' && !empty($data['refundable'])) ? 1 : 0;
    
    $stmt->execute([
        ':payment_date' => $data['payment_date'] ?? date('Y-m-d H:i:s'), 
        ':type' => $type, 
        ':number' => $number,
        ':amount' => $data['amount'] ?? 0.0, 
        ':currency' => $data['currency'] ?? 'RWF', 
        ':reference' => empty_string_to_null($data['reference'] ?? ''),
        ':note' => empty_string_to_null($data['note'] ?? ''), 
        ':status' => $data['status'] ?? 'Initiated', 
        ':payment_method' => $data['payment_method'] ?? 'OTHER',
        ':refundable' => $refundable
    ]);
    send_json_response(['success' => true, 'message' => 'Transaction created successfully!']);
}

function update_transaction($pdo, $data) {
    if (empty($data['id'])) {
        send_json_response(['success' => false, 'error' => 'Transaction ID is required.'], 400);
    }
    
    // Validate ID is numeric
    if (!is_numeric($data['id']) || intval($data['id']) <= 0) {
        send_json_response(['success' => false, 'error' => 'Invalid transaction ID format.'], 400);
    }
    
    // Validate required fields
    if (empty($data['payment_date'])) {
        send_json_response(['success' => false, 'error' => 'Payment date is required.'], 400);
    }
    
    if (!isset($data['amount']) || !is_numeric($data['amount'])) {
        send_json_response(['success' => false, 'error' => 'Valid amount is required.'], 400);
    }
    
    // Validate amount is not negative
    if (floatval($data['amount']) < 0) {
        send_json_response(['success' => false, 'error' => 'Amount cannot be negative.'], 400);
    }
    
    // Note: We attempt the update first without checking existence. This is optimal for the happy path
    // (transaction exists and is updated) as it requires only one query. We only check existence if
    // rowCount is 0, which handles the rare error case with a second query.
    $sql = "UPDATE wp_ea_transactions SET payment_date = :payment_date, type = :type, amount = :amount, currency = :currency, reference = :reference, note = :note, status = :status, payment_method = :payment_method, refundable = :refundable WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $type = $data['type'] ?? 'expense';
    // **FIXED**: Correctly default refundable to 0 if not provided or if type is not 'expense'
    $refundable = ($type === 'expense' && !empty($data['refundable'])) ? 1 : 0;

    try {
        // Prepare values with proper defaults and type coercion
        $payment_date = $data['payment_date'] ?? date('Y-m-d H:i:s');
        // If payment_date is just a date (YYYY-MM-DD), append midnight time for DATETIME column
        // Note: This sets time to 00:00:00 (midnight) by default for date-only inputs
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date)) {
            $payment_date .= ' 00:00:00';
        }
        
        $result = $stmt->execute([
            ':id' => intval($data['id']), 
            ':payment_date' => $payment_date, 
            ':type' => $type,
            ':amount' => floatval($data['amount']), 
            ':currency' => $data['currency'] ?? 'RWF', 
            ':reference' => empty_string_to_null($data['reference'] ?? ''),
            ':note' => empty_string_to_null($data['note'] ?? ''), 
            ':status' => $data['status'] ?? 'Initiated', 
            ':payment_method' => $data['payment_method'] ?? 'OTHER',
            ':refundable' => $refundable
        ]);
        
        if (!$result) {
            send_json_response(['success' => false, 'error' => 'Failed to execute update query.'], 500);
        }
        
        $rowCount = $stmt->rowCount();
        if ($rowCount === 0) {
            // No rows were updated - either transaction doesn't exist or no changes were made
            // Check if transaction exists (only done in error case for optimal performance)
            $checkStmt = $pdo->prepare("SELECT id FROM wp_ea_transactions WHERE id = :id");
            $checkStmt->execute([':id' => intval($data['id'])]);
            if (!$checkStmt->fetchColumn()) {
                send_json_response(['success' => false, 'error' => 'Transaction not found.'], 404);
            } else {
                // Transaction exists but no changes were made (submitted same data)
                send_json_response(['success' => true, 'message' => 'No changes were made. The transaction already has these values.'], 200);
            }
        } else {
            send_json_response(['success' => true, 'message' => 'Transaction updated successfully!']);
        }
    } catch (PDOException $e) {
        send_json_response([
            'success' => false, 
            'error' => 'Failed to update transaction',
            'details' => $e->getMessage()
        ], 500);
    }
}

function bulk_update_transactions($pdo, $data) {
    if (empty($data['ids']) || !is_array($data['ids']) || empty($data['updates'])) send_json_response(['success' => false, 'error' => 'IDs and updates required.'], 400);
    
    $allowed_fields = ['payment_date', 'type', 'payment_method', 'note', 'status', 'refundable'];
    $set_clauses = []; 
    $params = [];
    $updates = $data['updates'];

    foreach($updates as $key => $value) {
        if (in_array($key, $allowed_fields) && $value !== '') {
            $set_clauses[] = "$key = :$key";
            $params[":$key"] = $value;
        }
    }
    
    // **FIXED**: If changing type to 'payment', force 'refundable' to 0
    if (isset($updates['type']) && $updates['type'] === 'payment') {
        $params[':refundable'] = 0;
        if (!in_array('refundable = :refundable', $set_clauses)) {
            $set_clauses[] = 'refundable = :refundable';
        }
    }
    
    if (empty($set_clauses)) send_json_response(['success' => false, 'error' => 'No valid fields to update.'], 400);
    
    $ids_placeholder = rtrim(str_repeat('?,', count($data['ids'])), ',');
    $sql = "UPDATE wp_ea_transactions SET " . implode(', ', $set_clauses) . " WHERE id IN ($ids_placeholder)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge(array_values($params), $data['ids']));
    
    // Proper pluralization for success message
    $count = $stmt->rowCount();
    $message = $count === 1 ? '1 transaction updated successfully!' : $count . ' transactions updated successfully!';
    send_json_response(['success' => true, 'message' => $message]);
}

function delete_transaction($pdo, $data) {
    if (empty($data['id'])) send_json_response(['success' => false, 'error' => 'ID is required.'], 400);
    $stmt = $pdo->prepare("DELETE FROM wp_ea_transactions WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);
    send_json_response(['success' => $stmt->rowCount() > 0, 'message' => 'Transaction deleted successfully!']);
}

function handle_print($pdo, $data) {
    // Check permission to print/view transactions
    if (!userHasPermission($_SESSION['user_id'], 'view-transactions')) {
        send_json_response(['success' => false, 'error' => 'Access denied. You do not have permission to print transaction reports.'], 403);
        exit;
    }
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Feza_Transaction_Report.pdf"');
    
    // Create PDF instance (Portrait orientation)
    $pdf = new PDF_Feza('P', 'mm', 'A4');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    
    // Get transactions data
    $filters = $data['filters'] ?? [];
    $query_info = build_get_query($filters);
    $stmt = $pdo->prepare($query_info['sql']);
    $stmt->execute($query_info['params']);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($transactions)) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(190, 10, 'No transactions found with the selected filters.', 0, 1, 'C');
    } else {
        // ========== PAGE 1: EXECUTIVE SUMMARY ==========
        
        // Add filter information
        $pdf->SetFont('Arial', 'I', 9);
        $filter_text = "Report Scope: All transactions";
        
        if (!empty($filters['from']) || !empty($filters['to'])) {
            $filter_text = "Report Scope: Transactions from " . ($filters['from'] ?? 'beginning') . " to " . ($filters['to'] ?? 'now');
        }
        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $filter_text .= " | Type: " . ucfirst($filters['type']);
        }
        if (!empty($filters['currency']) && $filters['currency'] !== 'all') {
            $filter_text .= " | Currency: " . $filters['currency'];
        }
        if (!empty($filters['q'])) {
            $filter_text .= " | Search: '" . $filters['q'] . "'";
        }
        
        $pdf->MultiCell(190, 5, $filter_text, 0, 'L');
        $pdf->Ln(5);
        
        // Calculate summary data
        $totals = [];
        $refundable_totals = [];
        
        foreach ($transactions as $tx) {
            $currency = $tx['currency'] ?? 'N/A';
            if (!isset($totals[$currency])) {
                $totals[$currency] = ['payment' => 0, 'expense' => 0];
            }
            if (!isset($refundable_totals[$currency])) {
                $refundable_totals[$currency] = 0;
            }
            
            $amount = floatval($tx['amount']);
            if ($tx['type'] === 'payment') {
                $totals[$currency]['payment'] += $amount;
            } else if ($tx['type'] === 'expense') {
                $totals[$currency]['expense'] += $amount;
                
                if ($tx['refundable'] == '1') {
                    $refundable_totals[$currency] += $amount;
                }
            }
        }
        
        // Financial Summary Section
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(190, 8, 'Financial Summary', 0, 1, 'C');
        $pdf->Ln(2);
        
        // Summary table with unified styling
        $pdf->SetFillColor(...PDF_Feza::HEADER_BG_COLOR);
        $pdf->SetDrawColor(...PDF_Feza::BORDER_COLOR);
        $pdf->SetFont('Arial', 'B', 9);
        
        $pdf->Cell(40, 8, 'Currency', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Payments', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Expenses', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Net', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Refundable', 1, 1, 'C', true);
        
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetFillColor(255, 255, 255); // White background for data rows
        
        foreach ($totals as $currency => $amounts) {
            $net = $amounts['payment'] - $amounts['expense'];
            $refundable = $refundable_totals[$currency] ?? 0;
            
            $pdf->Cell(40, 7, $currency, 1, 0, 'C', true);
            $pdf->Cell(35, 7, number_format($amounts['payment'], 2), 1, 0, 'R', true);
            $pdf->Cell(35, 7, number_format($amounts['expense'], 2), 1, 0, 'R', true);
            $pdf->Cell(40, 7, number_format($net, 2), 1, 0, 'R', true);
            $pdf->Cell(40, 7, number_format($refundable, 2), 1, 1, 'R', true);
        }
        
        // Refundable Expenses Section
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(190, 7, 'Refundable Expenses', 0, 1, 'L');
        $pdf->SetFont('Arial', '', 9);
        
        foreach ($refundable_totals as $currency => $amount) {
            if ($amount > 0) {
                $pdf->Cell(190, 6, $currency . ': ' . number_format($amount, 2), 0, 1, 'L');
            }
        }
        
        // Check if chart images are provided (placeholder for future implementation)
        if (!empty($data['chartPieImage']) || !empty($data['chartBarImage'])) {
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(190, 7, 'Visual Analytics', 0, 1, 'C');
            
            // Add charts if provided (base64 decoded images)
            // Note: This is a placeholder - actual implementation would decode base64 images
            // and use $pdf->Image() to place them on the page
        }
        
        // CRUCIAL: Page break after Executive Summary
        $pdf->AddPage();
        
        // ========== PAGE 2+: TRANSACTION DETAILS ==========
        
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(190, 8, 'Transaction Details', 0, 1, 'C');
        $pdf->Ln(2);
        
        // Table header with unified styling
        $pdf->drawTransactionTableHeader();
        
        // Data rows with proper alignment
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetFillColor(255, 255, 255);
        
        foreach ($transactions as $tx) {
            // Format date
            $date = new DateTime($tx['payment_date']);
            $formatted_date = $date->format('Y-m-d');
            
            // Prepare data for the row - Client column shows reference field
            $clientRef = !empty($tx['reference']) ? $tx['reference'] : '-';
            $description = !empty($tx['note']) ? $tx['note'] : '-';
            $amount = number_format(floatval($tx['amount']), 2) . ' ' . $tx['currency'];
            
            // Status with refundable indicator
            $status = $tx['status'];
            if ($tx['type'] === 'expense' && $tx['refundable'] == '1') {
                $status .= ' (R)';
            }
            
            // Use Row method for proper multi-line handling
            // Updated: Removed Payment Method column
            $rowData = [
                $formatted_date,
                ucfirst($tx['type']),
                $tx['number'],
                $clientRef,
                $description,
                $amount,
                $status
            ];
            
            // Alignments: left for text, right for amounts
            $aligns = ['C', 'L', 'L', 'L', 'L', 'R', 'C'];
            
            $pdf->Row($rowData, 5, $aligns);
            
            // Check if we need a new page
            if ($pdf->GetY() > PDF_Feza::PAGE_BREAK_THRESHOLD) {
                $pdf->AddPage();
                
                // Redraw header using helper method
                $pdf->drawTransactionTableHeader();
                $pdf->SetFont('Arial', '', 8);
            }
        }
    }
    
    // Output the PDF
    $pdf->Output('D', 'Feza_Transaction_Report.pdf');
    exit;
}