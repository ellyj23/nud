<?php
session_start();

// --- Authenticate User ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

// --- Get user info from session ---
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? '';
$first_name = $_SESSION['first_name'] ?? $username; // Use first_name if available

// Load RBAC functions
if (file_exists(__DIR__ . '/rbac.php')) {
    require_once __DIR__ . '/rbac.php';
}

// Check if user has manage-roles permission
$canManageRoles = false;
if (isset($_SESSION['user_id']) && function_exists('userHasPermission')) {
    $canManageRoles = userHasPermission($_SESSION['user_id'], 'manage-roles');
}

// --- Avatar generation using initials ---
$initials = strtoupper(substr($first_name, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard - Feza Logistics</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="assets/js/email-document.js"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/design-system.css">
    <link rel="stylesheet" href="assets/css/application.css">
    <link rel="stylesheet" href="assets/css/ai-chat.css">
    
    <style>
        /* Additional styles specific to dashboard that extend the design system */
        .page-title {
            color: var(--text-primary);
            text-align: center;
            font-weight: var(--font-weight-bold);
            margin: 0;
            padding-top: var(--space-8);
        }
        
        /* Enhanced financial summary cards */
        .financial-summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--space-6);
            margin: var(--space-8);
            margin-bottom: var(--space-6);
        }
        
        .summary-card {
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-muted));
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            border: 1px solid var(--border-primary);
            box-shadow: var(--shadow-lg);
            transition: all var(--transition-base);
            position: relative;
            overflow: hidden;
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-500), var(--primary-400));
        }
        
        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-2xl);
        }
        
        .summary-card-title {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: var(--space-2);
        }
        
        .summary-card-value {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            color: var(--text-primary);
            margin-bottom: var(--space-1);
            transition: opacity 0.4s ease-in-out;
            opacity: 1;
        }

        .summary-card-value.fading {
            opacity: 0;
        }
        
        .summary-card-change {
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
        }
        .summary-card-change.positive { color: var(--success-700); }
        .summary-card-change.negative { color: var(--error-700); }
        
        /* Redesigned Summary Bar */
        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--space-4);
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .summary-bar {
            display: flex;
            gap: var(--space-5);
            padding: var(--space-3) var(--space-5);
            border-radius: var(--radius-lg);
            border: 1px solid transparent;
            transition: all var(--transition-base);
        }
        .summary-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .summary-item .label {
            font-size: var(--font-size-xs);
            color: var(--text-inverse);
            font-weight: var(--font-weight-medium);
            opacity: 0.8;
            white-space: nowrap;
        }
        .summary-item .value {
            font-size: var(--font-size-lg);
            color: var(--text-inverse);
            font-weight: var(--font-weight-bold);
            font-family: var(--font-family-mono);
            white-space: nowrap;
        }
        /* Visual styling for summary bar states */
        .summary-bar.summary-current { background: linear-gradient(135deg, var(--gray-600), var(--gray-700)); border-color: var(--gray-500); }
        .summary-bar.summary-rwf { background: linear-gradient(135deg, var(--primary-600), var(--primary-700)); border-color: var(--primary-500); }
        .summary-bar.summary-usd { background: linear-gradient(135deg, var(--success-600), var(--success-700)); border-color: var(--success-500); }
        .summary-bar.summary-eur { background: linear-gradient(135deg, var(--warning-500), var(--warning-600)); border-color: var(--warning-400); }


        /* Horizontal & Responsive Forms */
        .form-section {
            background-color: var(--bg-primary);
            border-radius: var(--radius-lg);
            padding: var(--space-6);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-primary);
        }
        .form-section h3 {
            margin: 0 0 var(--space-4) 0;
            color: var(--text-primary);
            font-size: var(--font-size-lg);
        }
        .form-grid-horizontal {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: var(--space-4);
            align-items: end;
        }
        .form-grid-horizontal .form-group {
            margin-bottom: 0;
        }
        .form-grid-horizontal .form-group.submit-group {
            grid-column: -2 / -1; /* Align to the end */
        }

        /* **FIXED**: Full width table container styling */
        .full-width-table-container {
            width: 100%;
            padding: 0; /* Remove side padding to allow card to go edge-to-edge */
        }
        .table-card {
            margin: 0;
            border-radius: 0; /* No radius for a seamless edge-to-edge look */
            border-left: none; /* Remove side borders */
            border-right: none;
        }
        .table-responsive-wrapper {
            overflow-x: auto; /* This enables the horizontal scrollbar on the table wrapper */
        }

        /* **FIXED**: Professional Table Actions styling */
        .table-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2); /* Increased gap for better spacing */
        }
        /* General style for all action icon buttons */
        .table-actions .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;   /* Slightly larger for easier clicking */
            height: 36px;  /* Maintain aspect ratio */
            border-radius: var(--radius-full); /* Make them circular */
            transition: all 0.2s ease-in-out;
            border: 1px solid transparent;
        }
        .table-actions .btn-icon:hover {
            transform: scale(1.1); /* Add a subtle zoom effect on hover */
        }
        /* Edit Button */
        .table-actions .btn-edit {
            color: var(--warning-600);
            background-color: var(--warning-100);
        }
        .table-actions .btn-edit:hover {
            background-color: var(--warning-200);
            border-color: var(--warning-300);
        }
        /* Delete Button */
        .table-actions .btn-delete {
            color: var(--error-600);
            background-color: var(--error-100);
        }
        .table-actions .btn-delete:hover {
            background-color: var(--error-200);
            border-color: var(--error-300);
        }
        /* More Actions Button (...) */
        .table-actions .actions-menu-btn {
            color: var(--gray-600);
            background-color: var(--gray-100);
        }
        .table-actions .actions-menu-btn:hover {
            background-color: var(--gray-200);
            border-color: var(--gray-300);
        }
        /* Dropdown menu styling (remains largely the same) */
        .table-actions .actions-menu { position: relative; }
        .table-actions .actions-dropdown {
            display: none; position: absolute; right: 0; top: calc(100% + 4px);
            background-color: var(--bg-primary); border-radius: var(--radius-base); box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-primary); z-index: 10; width: 150px; padding: var(--space-2) 0; overflow: hidden;
        }
        .table-actions .actions-dropdown.show { display: block; }
        .table-actions .actions-dropdown a {
            display: flex; align-items: center; gap: var(--space-3); padding: var(--space-2) var(--space-4);
            font-size: var(--font-size-sm); color: var(--text-secondary); text-decoration: none;
        }
        .table-actions .actions-dropdown a:hover { background-color: var(--bg-muted); color: var(--primary); text-decoration: none; }
        .table-actions .actions-dropdown a.disabled { 
            color: #ccc; cursor: not-allowed; opacity: 0.5; 
        }
        .table-actions .actions-dropdown a.disabled:hover { 
            background-color: transparent; color: #ccc; 
        }
        .table-actions .actions-dropdown a svg { width: 16px; height: 16px; }

        /* Responsive improvements */
        @media (max-width: 992px) {
            .top-actions, .full-width-table-container { padding-left: var(--space-4); padding-right: var(--space-4); }
            .top-actions { flex-direction: column; align-items: stretch; }
            .summary-bar { flex-wrap: wrap; justify-content: center; }
        }

        /* ========== ENHANCED STYLING WITH !IMPORTANT OVERRIDES ========== */
        /* Status Badges - Enhanced with solid backgrounds and !important */
        .status-indicator.status-not-paid, 
        .status-badge.not-paid, 
        .status-badge-not-paid {
            background-color: #dc3545 !important;
            color: white !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
        }

        .status-indicator.status-not-paid::before {
            display: none !important;
        }

        .status-indicator.status-partially-paid,
        .status-badge.partial, 
        .status-badge-partial {
            background-color: #ffc107 !important;
            color: #212529 !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
        }

        .status-indicator.status-partially-paid::before {
            display: none !important;
        }

        .status-indicator.status-paid,
        .status-badge.paid, 
        .status-badge-paid {
            background-color: #28a745 !important;
            color: white !important;
            padding: 4px 12px !important;
            border-radius: 20px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            border: none !important;
        }

        .status-indicator.status-paid::before {
            display: none !important;
        }

        /* Service Type Badges - Enhanced with solid backgrounds */
        .service-badge.import, 
        .service-badge-import {
            background-color: #007bff !important;
            color: white !important;
            padding: 3px 10px !important;
            border-radius: 4px !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            border: none !important;
        }

        .service-badge.export, 
        .service-badge-export {
            background-color: #6f42c1 !important;
            color: white !important;
            padding: 3px 10px !important;
            border-radius: 4px !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            border: none !important;
        }

        .service-badge.customs,
        .service-badge-customs {
            background-color: #17a2b8 !important;
            color: white !important;
            padding: 3px 10px !important;
            border-radius: 4px !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            border: none !important;
        }

        .service-badge.transport,
        .service-badge-transport {
            background-color: #fd7e14 !important;
            color: white !important;
            padding: 3px 10px !important;
            border-radius: 4px !important;
            font-size: 11px !important;
            font-weight: 500 !important;
            border: none !important;
        }

        /* Bold Amount and Due columns */
        td.amount-column, 
        td.due-column,
        .enhanced-table tbody td:nth-child(9), 
        .enhanced-table tbody td:nth-child(12) {
            font-weight: 700 !important;
            color: #212529 !important;
        }

        /* Zebra striping - Enhanced */
        .enhanced-table tbody tr:nth-child(even),
        #clientTable tbody tr:nth-child(even) {
            background-color: #f8f9fa !important;
        }

        /* Row hover - Enhanced */
        .enhanced-table tbody tr:hover,
        #clientTable tbody tr:hover {
            background-color: #e3f2fd !important;
            box-shadow: inset 0 0 0 1px rgba(33, 150, 243, 0.3) !important;
        }

        /* Quick filter chips - Enhanced */
        .filter-chip {
            display: inline-flex !important;
            align-items: center !important;
            padding: 8px 16px !important;
            margin: 0 4px !important;
            border-radius: 20px !important;
            background-color: #e9ecef !important;
            color: #495057 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            border: 2px solid transparent !important;
            font-weight: 500 !important;
        }

        .filter-chip.active {
            background-color: #007bff !important;
            color: white !important;
            border-color: #0056b3 !important;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3) !important;
        }

        .filter-chip:hover {
            background-color: #007bff !important;
            color: white !important;
            border-color: #0056b3 !important;
            transform: translateY(-1px) !important;
        }

        .filter-chip .count {
            margin-left: 8px !important;
            padding: 2px 8px !important;
            background-color: rgba(255, 255, 255, 0.3) !important;
            border-radius: 12px !important;
            font-size: 11px !important;
            font-weight: 600 !important;
        }

        /* Export buttons styling */
        .btn-group button,
        .action-group button {
            font-weight: 500 !important;
            transition: all 0.2s ease !important;
        }

        .btn-group button:hover,
        .action-group button:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15) !important;
        }

        /* ========== CRITICAL TABLE TEXT WRAPPING FIXES ========== */
        /* 1. Force ALL table cells to never wrap */
        #clientTable td,
        #clientTable th,
        .enhanced-table td,
        .enhanced-table th {
            white-space: nowrap !important;
        }

        /* 2. Fix the Responsible column - Avatar LEFT of Name (horizontal layout) */
        .user-info {
            display: inline-flex !important;
            flex-direction: row !important;
            align-items: center !important;
            gap: 0.5rem !important;
            white-space: nowrap !important;
            flex-wrap: nowrap !important;
        }

        .user-avatar {
            flex-shrink: 0 !important;
        }

        .user-name {
            white-space: nowrap !important;
        }

        /* 3. Fix Date column - Date on one line, time-ago below */
        .date-container {
            display: inline-flex !important;
            flex-direction: column !important;
            white-space: nowrap !important;
        }

        .date-main {
            display: block !important;
            white-space: nowrap !important;
        }

        .time-ago {
            display: block !important;
            white-space: nowrap !important;
            font-size: 0.65rem !important;
            font-style: italic !important;
            font-weight: 600 !important;
            color: #6b7280 !important;
        }

        /* 4. Set minimum table width to force horizontal scroll */
        .enhanced-table,
        #clientTable {
            min-width: 1600px !important;
            table-layout: auto !important;
        }

        .table-responsive-wrapper {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        /* 5. Ensure all nested elements don't wrap */
        #clientTable td *,
        .enhanced-table td * {
            white-space: nowrap !important;
        }

        /* 6. Status badges should not wrap */
        .status-indicator,
        .status-badge {
            white-space: nowrap !important;
            display: inline-flex !important;
        }
        
        /* ========== PAGINATION STYLES ========== */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: var(--space-4);
            padding: var(--space-4);
            background: var(--bg-primary);
            border-radius: var(--radius-base);
        }
        
        .pagination-info {
            font-size: var(--font-size-sm);
            color: var(--text-muted);
        }
        
        .pagination-controls {
            display: flex;
            gap: var(--space-2);
            align-items: center;
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid var(--border-primary);
            background: var(--bg-primary);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: var(--primary-500);
            color: white;
            border-color: var(--primary-500);
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-btn.active {
            background: var(--primary-500);
            color: white;
            border-color: var(--primary-500);
        }
        
        .pagination-ellipsis {
            padding: 8px 4px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<script>
    // Inactivity timer script is preserved
    (function() {
        const LOGOUT_TIME = 5 * 60 * 1000; const WARNING_TIME = 4 * 60 * 1000;
        let logoutTimer, warningTimer;
        const logoutUser = () => { window.location.href = 'logout.php'; };
        const showWarning = () => { if (document.getElementById('inactivity-warning')) return; const warningDiv = document.createElement('div'); warningDiv.id = 'inactivity-warning'; warningDiv.innerHTML = 'You will be logged out in 1 minute due to inactivity. '; Object.assign(warningDiv.style, { position: 'fixed', top: '20px', left: '50%', transform: 'translateX(-50%)', padding: '15px 25px', backgroundColor: '#dc3545', color: 'white', borderRadius: '5px', zIndex: '9999', boxShadow: '0 4px 8px rgba(0,0,0,0.2)' }); const stayButton = document.createElement('button'); stayButton.innerText = 'Stay Logged In'; Object.assign(stayButton.style, { marginLeft: '15px', padding: '5px 10px', cursor: 'pointer', border: '1px solid white', backgroundColor: '#0071ce', color: 'white' }); stayButton.onclick = () => resetTimers(); warningDiv.appendChild(stayButton); document.body.appendChild(warningDiv); };
        const resetTimers = () => { clearTimeout(warningTimer); clearTimeout(logoutTimer); const warningDiv = document.getElementById('inactivity-warning'); if (warningDiv) warningDiv.remove(); warningTimer = setTimeout(showWarning, WARNING_TIME); logoutTimer = setTimeout(logoutUser, LOGOUT_TIME); };
        ['click', 'mousemove', 'keydown', 'scroll'].forEach(event => document.addEventListener(event, resetTimers, true));
        resetTimers();
    })();
</script>

<div class="app-container">
    <header class="header-container">
        <a href="index.php" class="logo">Feza Logistics</a>
        <div id="forex-channel" class="forex-channel" title="Live FOREX Rates (Base: USD)">
            <div class="forex-list-wrapper"><ul class="forex-list"><li>Loading...</li></ul></div>
        </div>
        <div class="user-menu">
            <div class="user-avatar" id="avatar-button"><?php echo htmlspecialchars($initials); ?></div>
            <ul class="dropdown-menu" id="dropdown-menu">
                <li><a href="profile.php">Manage Profile</a></li>
                <li><a href="document_list.php">My Documents</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <?php if ($canManageRoles): ?>
                <li class="divider"></li>
                <li><a href="manage_roles.php">Role Management</a></li>
                <?php endif; ?>
                <li class="divider"></li>
                <li><a href="create_quotation.php">Create Quotation</a></li>
                <li><a href="create_invoice.php">Create Invoice</a></li>
                <li><a href="create_receipt.php">Create Receipt</a></li>
                <li class="divider"></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>

    <main>
        <h1 class="page-title">Financial Dashboard</h1>

        <!-- Financial Summary Cards -->
        <div class="financial-summary-cards px-8">
            <div class="summary-card">
                <div class="summary-card-title">Total Revenue</div>
                <div class="summary-card-value" id="totalRevenue">Loading...</div>
                <div class="summary-card-change" id="revenueChange">&nbsp;</div>
            </div>
            <div class="summary-card">
                <div class="summary-card-title">Outstanding Amount</div>
                <div class="summary-card-value" id="outstandingAmount">Loading...</div>
                <div class="summary-card-change" id="outstandingChange">&nbsp;</div>
            </div>
            <div class="summary-card">
                <div class="summary-card-title">Total Clients</div>
                <div class="summary-card-value" id="totalClients">Loading...</div>
                <div class="summary-card-change" id="clientsChange">&nbsp;</div>
            </div>
        </div>

        <!-- Top Action Bar -->
        <div class="top-actions px-8 mb-6">
            <div class="action-buttons-group">
                <button id="showAddFormBtn" class="btn btn-primary">Add New Client</button>
                <button id="importExcelBtn" class="btn btn-secondary btn-icon" title="Import from Excel"><svg fill="currentColor" viewBox="0 0 24 24" width="18" height="18"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M13.5,16V19H11.5V16H8.5V14H11.5V11H13.5V14H16.5V16H13.5M13,9V3.5L18.5,9H13Z" /></svg></button>
                <input type="file" id="importExcelFile" style="display: none;" accept=".xlsx, .xls">
                <button id="downloadExcelBtn" class="btn btn-secondary btn-icon" title="Download as Excel"><svg fill="currentColor" viewBox="0 0 24 24" width="18" height="18"><path d="M5,20H19V18H5M19,9H15V3H9V9H5L12,16L19,9Z" /></svg></button>
                <button id="printTableBtn" class="btn btn-secondary btn-icon" title="Print Table"><svg fill="currentColor" viewBox="0 0 24 24" width="18" height="18"><path d="M19,8H5A3,3 0 0,0 2,11V17H6V21H18V17H22V11A3,3 0 0,0 19,8M16,19H8V14H16M19,12A1,1 0 0,1 20,13A1,1 0 0,1 19,14A1,1 0 0,1 18,13A1,1 0 0,1 19,12M18,3H6V7H18V3Z" /></svg></button>
                <button id="viewAllBtn" class="btn btn-secondary">View All</button>
            </div>
            <div id="summaryBar" class="summary-bar">
                <!-- Content generated by JS -->
            </div>
        </div>
        
        <!-- Add Client Form -->
        <div id="addClientCard" class="form-section mx-8 mb-6" style="display: none;">
            <h3>Add New Client</h3>
            <form id="clientForm">
                <div class="form-grid-horizontal">
                    <div class="form-group"><label class="form-label">Reg No</label><input type="text" name="reg_no" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Client Name</label><input type="text" name="client_name" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Date</label><input type="date" name="date" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Responsible</label><input type="text" name="Responsible" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">TIN</label><input type="text" name="TIN" class="form-control" maxlength="9" pattern="[0-9]{1,9}" title="Enter up to 9 digits" placeholder="9 digits max"></div>
                    <div class="form-group"><label class="form-label">Service</label><input type="text" name="service" class="form-control" required></div>
                    <div class="form-group"><label class="form-label">Amount</label><input type="number" name="amount" class="form-control" step="0.01" required></div>
                    <div class="form-group"><label class="form-label">Currency</label><select name="currency" class="form-control form-select" required><option value="USD" selected>USD</option><option value="EUR">EUR</option><option value="RWF">RWF</option></select></div>
                    <div class="form-group"><label class="form-label">Paid</label><input type="number" name="paid_amount" class="form-control" step="0.01" required></div>
                    <div class="form-group"><label class="form-label">Due</label><input type="number" name="due_amount" class="form-control" readonly></div>
                    <div class="form-group submit-group"><button type="submit" class="btn btn-primary w-full">Save Client</button></div>
                </div>
            </form>
        </div>

        <!-- Filter Section -->
        <div class="form-section mx-8 mb-6">
            <h3>Filter & Search</h3>
            
            <!-- Quick Filter Chips -->
            <div class="filter-chips" id="quickFilters">
                <div class="filter-chip active" data-status="">
                    <span>All</span>
                    <span class="count" id="count-all">0</span>
                </div>
                <div class="filter-chip" data-status="NOT PAID">
                    <span>Not Paid</span>
                    <span class="count" id="count-not-paid">0</span>
                </div>
                <div class="filter-chip" data-status="PARTIALLY PAID">
                    <span>Partially Paid</span>
                    <span class="count" id="count-partial">0</span>
                </div>
                <div class="filter-chip" data-status="PAID">
                    <span>Paid</span>
                    <span class="count" id="count-paid">0</span>
                </div>
            </div>
            
            <div id="filterContainer" class="form-grid-horizontal">
                <div class="form-group"><label for="search" class="form-label">Search</label><input type="text" id="search" class="form-control" placeholder="Reg No, Name, Phone..."></div>
                <div class="form-group"><label for="filterDateFrom" class="form-label">From</label><input type="date" id="filterDateFrom" class="form-control"></div>
                <div class="form-group"><label for="filterDateTo" class="form-label">To</label><input type="date" id="filterDateTo" class="form-control"></div>
                <div class="form-group"><label for="filterPaidStatus" class="form-label">Status</label><select id="filterPaidStatus" class="form-control form-select"><option value="">All</option><option value="PAID">Paid</option><option value="PARTIALLY PAID">Partially Paid</option><option value="NOT PAID">Not Paid</option></select></div>
                <div class="form-group"><label for="filterCurrency" class="form-label">Currency</label><select id="filterCurrency" class="form-control form-select"><option value="">All</option><option value="RWF">RWF</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div>
                <div class="form-group"><label for="entriesPerPage" class="form-label">Show</label><select id="entriesPerPage" class="form-control form-select"><option value="20">20 entries</option><option value="50">50 entries</option><option value="100">100 entries</option><option value="200">200 entries</option><option value="500">500 entries</option><option value="999999">All</option></select></div>
            </div>
        </div>
    
        <!-- Data Table -->
        <div class="full-width-table-container">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar mx-8" id="bulkActionsBar">
                <div class="bulk-actions-info">
                    <span id="selectedCount">0</span> items selected
                </div>
                <div class="bulk-actions-buttons">
                    <button class="btn btn-secondary btn-sm" id="bulkExportBtn">Export Selected</button>
                    <button class="btn btn-danger btn-sm" id="bulkDeleteBtn">Delete Selected</button>
                    <button class="btn btn-secondary btn-sm" id="clearSelectionBtn">Clear Selection</button>
                </div>
            </div>
            
            <div class="enhanced-card table-card">
                <div class="table-responsive-wrapper">
                    <table id="clientTable" class="enhanced-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="bulk-checkbox" id="selectAllCheckbox" title="Select All"></th>
                                <th>#</th>
                                <th class="sortable" data-column="reg_no">Reg No</th>
                                <th class="sortable" data-column="client_name">Client Name</th>
                                <th class="sortable" data-column="date">Date</th>
                                <th>Responsible</th>
                                <th>TIN</th>
                                <th>Service</th>
                                <th class="sortable" data-column="amount">Amount</th>
                                <th>Currency</th>
                                <th class="sortable" data-column="paid_amount">Paid</th>
                                <th class="sortable" data-column="due_amount">Due</th>
                                <th class="sortable" data-column="status">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot id="tableSummary" style="display: none;">
                            <tr>
                                <td colspan="8" style="text-align: right; padding-right: 1rem;">TOTALS:</td>
                                <td id="totalAmount">0.00</td>
                                <td></td>
                                <td id="totalPaid">0.00</td>
                                <td id="totalDue">0.00</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- Pagination Controls -->
                <div class="pagination-container">
                    <div class="pagination-info" id="paginationInfo">
                        Showing <span id="showingStart">0</span>-<span id="showingEnd">0</span> of <span id="totalRecords">0</span> records
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Pagination buttons will be inserted here by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modals -->
<div id="historyModal" class="enhanced-modal" aria-hidden="true"><div class="modal-content"><div class="modal-header"><h3 id="historyModal-title" class="modal-title">Client History</h3></div><div id="historyModal-body"><p>Loading...</p></div><div class="modal-footer"><button type="button" id="closeHistoryModalBtn" class="btn btn-secondary">Close</button></div></div></div>
<div id="tinModal" class="enhanced-modal" aria-hidden="true"><div class="modal-content container-sm"><div class="modal-header"><h3 id="tinModal-title" class="modal-title">Enter TIN</h3></div><form id="tinForm" novalidate><input type="hidden" id="tin-clientId"><input type="hidden" id="tin-docType"><div class="form-group"><label for="tin-number" class="form-label">TIN Number</label><input type="text" id="tin-number" class="form-control" placeholder="Enter 9 digits" required><div class="form-error tin-error-message"></div></div><div class="modal-footer"><button type="button" id="closeTinModalBtn" class="btn btn-secondary">Cancel</button><button type="submit" class="btn btn-primary">Generate</button></div></form></div></div>
<div id="confirmModal" class="enhanced-modal" aria-hidden="true"><div class="modal-content container-sm text-center"><div class="modal-header"><h3 id="confirmModal-title" class="modal-title">Confirm Deletion</h3></div><p id="confirmModal-text">Are you sure?</p><div class="modal-footer"><button id="confirmModal-cancel" class="btn btn-secondary">Cancel</button><button id="confirmModal-confirm" class="btn btn-danger">Delete</button></div></div></div>
<div id="notification-toast" class="notification-toast"></div>
<div id="loading-overlay" class="loading-overlay"></div>

<script>
$(document).ready(function() {
    // --- Global State ---
    let dashboardStatsInterval, summaryBarInterval;
    let currencySummaries = {};
    let currentPage = 1;
    let totalPages = 1;
    let totalRecords = 0;
    let perPage = 20;

    // --- Helper Functions ---
    const showLoading = (show) => $('#loading-overlay').toggleClass('show', show);
    const showToast = (message, type = 'success') => {
        const toast = $('#notification-toast');
        toast.text(message).removeClass('success error show').addClass(type).addClass('show');
        setTimeout(() => toast.removeClass('show'), type === 'error' ? 5000 : 3000);
    };
    
    /**
     * **IMPROVED ERROR HANDLING**
     * This function provides more detailed error messages to help with debugging.
     * If a PHP error occurs, it will log the full response to the console.
     */
    const handleAjaxError = (jqXHR, defaultMessage) => {
        let error = defaultMessage;
        // Check for a structured JSON error response first
        if (jqXHR.responseJSON && jqXHR.responseJSON.error) {
            error = jqXHR.responseJSON.error;
            if (jqXHR.responseJSON.details) {
                console.error("Server Error Details:", jqXHR.responseJSON.details);
                error += " (See console for details)";
            }
        } 
        // If no JSON, it might be a fatal PHP error outputting HTML
        else if (jqXHR.responseText) {
            console.error("An unexpected server error occurred. Full server response:", jqXHR.responseText);
            error = "An unexpected server error occurred. Check browser console (F12) for details.";
        }
        showToast(error, 'error');
    };

    const showConfirm = (callback) => {
        $('#confirmModal').addClass('show').attr('aria-hidden', 'false');
        $('#confirmModal-confirm').off('click').one('click', () => {
            $('#confirmModal').removeClass('show').attr('aria-hidden', 'true');
            callback();
        });
        $('#confirmModal-cancel').off('click').one('click', () => $('#confirmModal').removeClass('show'));
    };
    const formatCurrency = (amount) => amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    /**
     * Calculate time ago from a timestamp
     */
    const getTimeAgo = (timestamp) => {
        if (!timestamp) return '';
        
        const now = new Date();
        const past = new Date(timestamp);
        const diffMs = now - past;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHr = Math.floor(diffMin / 60);
        const diffDays = Math.floor(diffHr / 24);
        const diffWeeks = Math.floor(diffDays / 7);
        const diffMonths = Math.floor(diffDays / 30);
        const diffYears = Math.floor(diffDays / 365);
        
        if (diffSec < 60) return 'just now';
        if (diffMin < 60) return `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`;
        if (diffHr < 24) return `${diffHr} hour${diffHr > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
        if (diffWeeks < 4) return `${diffWeeks} week${diffWeeks > 1 ? 's' : ''} ago`;
        if (diffMonths < 12) return `${diffMonths} month${diffMonths > 1 ? 's' : ''} ago`;
        return `${diffYears} year${diffYears > 1 ? 's' : ''} ago`;
    };

    // --- Data Fetching and Rendering ---

    /**
     * Fetches data from the server. Handles both initial load and filtered requests.
     */
    function loadData() {
        showLoading(true);
        const ajaxData = { 
            searchQuery: $('#search').val(), 
            filterDateFrom: $('#filterDateFrom').val(), 
            filterDateTo: $('#filterDateTo').val(), 
            filterPaidStatus: $('#filterPaidStatus').val(), 
            filterCurrency: $('#filterCurrency').val(),
            page: currentPage,
            limit: perPage
        };

        $.ajax({
            url: 'fetch_dashboard_data.php', // Always call the single, consolidated endpoint
            type: 'GET',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    renderTable(response.clients);
                    
                    // Update pagination state
                    if (response.pagination) {
                        currentPage = response.pagination.current_page;
                        totalPages = response.pagination.total_pages;
                        totalRecords = response.pagination.total_records;
                        perPage = response.pagination.per_page;
                        renderPagination();
                    }
                    
                    // Stats are now always returned and reflect overall totals (not just current page)
                    // Dashboard cards show the complete dataset regardless of pagination
                    if (response.stats) {
                        currencySummaries = response.stats.currencySummaries;
                        updateDashboardCards(response.stats);
                        startSummaryBarCycler();
                    }
                } else {
                    handleAjaxError({ responseJSON: response }, 'Failed to load data.');
                }
            },
            error: (jqXHR) => handleAjaxError(jqXHR, 'Server error while fetching data.'),
            complete: () => showLoading(false)
        });
    }

    function renderTable(clients) {
        cancelEditing();
        const tableBody = $('#clientTable tbody');
        if (!clients || !Array.isArray(clients)) {
            tableBody.html('<tr><td colspan="14" class="text-center p-8">Could not load client data.</td></tr>');
            return;
        }
        if (clients.length === 0) {
            tableBody.html('<tr><td colspan="14" class="text-center p-8">No clients found matching your criteria.</td></tr>');
            updateSummaryBarForFilteredView();
            updateStatusCounts(clients);
            return;
        }

        let rowsHtml = '';
        clients.forEach((client, index) => {
            // Calculate row number based on current page
            const rowNumber = (currentPage - 1) * perPage + index + 1;
            const statusClass = client.status.toLowerCase().replace(/ /g, '-');
            const statusIndicator = `<span class="status-indicator status-${statusClass}">${client.status}</span>`;
            
            // Calculate payment progress percentage
            const amount = parseFloat(client.amount) || 0;
            const paidAmount = parseFloat(client.paid_amount) || 0;
            const progressPercent = amount > 0 ? Math.round((paidAmount / amount) * 100) : 0;
            const progressClass = progressPercent === 100 ? 'complete' : (progressPercent > 0 ? 'partial' : 'none');
            const progressBar = `
                <div class="payment-progress" title="${progressPercent}% paid">
                    <div class="payment-progress-bar ${progressClass}" style="width: ${progressPercent}%"></div>
                </div>
            `;
            
            // Service type badge
            const serviceText = (client.service || '').toLowerCase();
            let serviceBadgeClass = '';
            if (serviceText.includes('import')) serviceBadgeClass = 'import';
            else if (serviceText.includes('export')) serviceBadgeClass = 'export';
            else if (serviceText.includes('customs')) serviceBadgeClass = 'customs';
            else if (serviceText.includes('transport')) serviceBadgeClass = 'transport';
            
            const serviceBadge = serviceBadgeClass 
                ? `<span class="service-badge ${serviceBadgeClass}">${client.service}</span>`
                : client.service;
            
            // Generate initials for responsible person
            const responsibleName = client.Responsible || client.phone_number || '';
            const initials = responsibleName.split(' ')
                .map(word => word.charAt(0).toUpperCase())
                .slice(0, 2)
                .join('');
            const responsibleWithAvatar = responsibleName 
                ? `<div class="user-info"><span class="user-avatar">${initials}</span><span class="user-name">${responsibleName}</span></div>`
                : '';
            
            // Format date with time ago counter
            // Use created_at if available, otherwise use date field
            // Ensure proper date format for JavaScript Date constructor
            const createdAt = client.created_at || (client.date ? client.date + ' 00:00:00' : null);
            const timeAgo = getTimeAgo(createdAt);
            const dateDisplay = timeAgo 
                ? `<div class="date-container"><span class="date-main">${client.date}</span><span class="time-ago">${timeAgo}</span></div>`
                : client.date;
            
            // Title Case for client names
            const clientNameTitleCase = client.client_name
                .toLowerCase()
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
            
            // Conditional receipt link based on paid amount
            const receiptLink = parseFloat(client.paid_amount) > 0 
                ? `<a href="#" class="print-link" data-type="receipt"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l4 4m-4-4l4-4m-1 12H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V15a2 2 0 01-2 2z"></path></svg><span>Receipt</span></a>`
                : `<a href="#" class="print-link disabled" data-type="receipt" title="No payment made"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l4 4m-4-4l4-4m-1 12H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V15a2 2 0 01-2 2z"></path></svg><span>Receipt</span></a>`;

            // Conditional email receipt link based on paid amount
            const emailReceiptLink = parseFloat(client.paid_amount) > 0 
                ? `<a href="#" class="email-link" data-type="receipt"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg><span>Email Receipt</span></a>`
                : `<a href="#" class="email-link disabled" data-type="receipt" title="No payment made"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg><span>Email Receipt</span></a>`;

            // Action buttons HTML with new classes for styling
            const actionButtons = `
                <div class="table-actions">
                    <button class="editBtn btn btn-icon btn-edit" title="Edit Row"><svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"></path><path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path></svg></button>
                    <button class="deleteBtn btn btn-icon btn-delete" title="Delete Row"><svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg></button>
                    <div class="actions-menu">
                        <button class="actions-menu-btn btn btn-icon" title="More Actions"><svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path></svg></button>
                        <div class="actions-dropdown">
                            <a href="#" class="print-link" data-type="invoice"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span>Invoice</span></a>
                            ${receiptLink}
                            <a href="#" class="email-link" data-type="invoice"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg><span>Email Invoice</span></a>
                            ${emailReceiptLink}
                            <a href="#" class="historyBtn"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span>History</span></a>
                        </div>
                    </div>
                </div>`;

            rowsHtml += `
                <tr data-id="${client.id}">
                    <td><input type="checkbox" class="bulk-checkbox row-checkbox" data-id="${client.id}"></td>
                    <td>${rowNumber}</td>
                    <td title="${client.reg_no}"><div class="truncate" style="max-width: 10ch;">${client.reg_no}</div></td>
                    <td title="${client.client_name}"><div class="truncate" style="max-width: 25ch;">${clientNameTitleCase}</div></td>
                    <td>${dateDisplay}</td>
                    <td>${responsibleWithAvatar}</td>
                    <td>${client.TIN || ''}</td>
                    <td title="${client.service}"><div class="truncate" style="max-width: 20ch;">${serviceBadge}</div></td>
                    <td>${formatCurrency(parseFloat(client.amount))}</td>
                    <td>${client.currency}</td>
                    <td><div class="paid-container">${formatCurrency(parseFloat(client.paid_amount))}${progressBar}</div></td>
                    <td>${formatCurrency(parseFloat(client.due_amount))}</td>
                    <td>${statusIndicator}</td>
                    <td>${actionButtons}</td>
                </tr>
            `;
        });
        tableBody.html(rowsHtml);
        
        // Apply search restriction if search is active
        applySearchRestriction();
        
        updateSummaryBarForFilteredView();
        updateStatusCounts(clients);
        updateTableSummary(clients);
        // Pagination info is updated in loadData after receiving pagination data
    }

    /**
     * Search restriction feature - hides rows containing special characters when search is active
     */
    function containsSpecialChars(str) {
        if (!str) return false;
        const specialCharsRegex = /[@#$%^&*!~`+=\[\]{}|\\<>]/;
        return specialCharsRegex.test(str);
    }

    function isRowSearchable(row) {
        // Check all cells in the row
        const cells = $(row).find('td');
        for (let i = 0; i < cells.length; i++) {
            const cellText = $(cells[i]).text();
            if (containsSpecialChars(cellText)) {
                return false; // Row is not searchable
            }
        }
        return true;
    }

    function applySearchRestriction() {
        const searchTerm = $('#search').val().trim();
        
        // Only apply restriction when there's an active search
        if (searchTerm) {
            const rows = $('#clientTable tbody tr');
            rows.each(function() {
                const row = $(this);
                // Skip if it's a "no data" row
                if (row.find('td').length === 1 && row.find('td').attr('colspan')) {
                    return;
                }
                
                // Hide non-searchable rows when search is active
                if (!isRowSearchable(this)) {
                    row.hide();
                }
            });
        }
    }
    
    // New function to update status counts in filter chips
    function updateStatusCounts(clients) {
        const counts = {
            all: clients.length,
            'NOT PAID': 0,
            'PARTIALLY PAID': 0,
            'PAID': 0
        };
        
        clients.forEach(client => {
            if (counts.hasOwnProperty(client.status)) {
                counts[client.status]++;
            }
        });
        
        $('#count-all').text(counts.all);
        $('#count-not-paid').text(counts['NOT PAID']);
        $('#count-partial').text(counts['PARTIALLY PAID']);
        $('#count-paid').text(counts['PAID']);
    }
    
    // New function to update table summary/totals row
    function updateTableSummary(clients) {
        if (clients.length === 0) {
            $('#tableSummary').hide();
            return;
        }
        
        let totalAmount = 0, totalPaid = 0, totalDue = 0;
        const currencies = new Set();
        
        clients.forEach(client => {
            totalAmount += parseFloat(client.amount) || 0;
            totalPaid += parseFloat(client.paid_amount) || 0;
            totalDue += parseFloat(client.due_amount) || 0;
            currencies.add(client.currency);
        });
        
        const currencyLabel = currencies.size === 1 ? Array.from(currencies)[0] : 'Mixed';
        
        $('#totalAmount').text(`${formatCurrency(totalAmount)} ${currencyLabel}`);
        $('#totalPaid').text(`${formatCurrency(totalPaid)} ${currencyLabel}`);
        $('#totalDue').text(`${formatCurrency(totalDue)} ${currencyLabel}`);
        $('#tableSummary').show();
    }
    
    // Function to update pagination info
    function updatePaginationInfo() {
        const start = totalRecords > 0 ? (currentPage - 1) * perPage + 1 : 0;
        const end = Math.min(currentPage * perPage, totalRecords);
        $('#showingStart').text(start);
        $('#showingEnd').text(end);
        $('#totalRecords').text(totalRecords);
    }
    
    // Function to render pagination controls
    function renderPagination() {
        updatePaginationInfo();
        const paginationControls = $('#paginationControls');
        paginationControls.empty();
        
        if (totalPages <= 1) {
            return; // Don't show pagination if only one page
        }
        
        // Previous button
        const prevBtn = $('<button>')
            .addClass('pagination-btn')
            .text('Previous')
            .prop('disabled', currentPage === 1)
            .on('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    loadData();
                }
            });
        paginationControls.append(prevBtn);
        
        // Page numbers with ellipsis
        const maxButtons = 7; // Max page buttons to show
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        // Adjust range if at the start or end
        if (currentPage <= 3) {
            endPage = Math.min(totalPages, maxButtons);
        } else if (currentPage >= totalPages - 2) {
            startPage = Math.max(1, totalPages - maxButtons + 1);
        }
        
        // First page
        if (startPage > 1) {
            const firstBtn = $('<button>')
                .addClass('pagination-btn')
                .text('1')
                .on('click', () => {
                    currentPage = 1;
                    loadData();
                });
            paginationControls.append(firstBtn);
            
            if (startPage > 2) {
                paginationControls.append($('<span>').addClass('pagination-ellipsis').text('...'));
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = $('<button>')
                .addClass('pagination-btn')
                .text(i)
                .toggleClass('active', i === currentPage)
                .on('click', (function(page) {
                    return function() {
                        currentPage = page;
                        loadData();
                    };
                })(i));
            paginationControls.append(pageBtn);
        }
        
        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationControls.append($('<span>').addClass('pagination-ellipsis').text('...'));
            }
            
            const lastBtn = $('<button>')
                .addClass('pagination-btn')
                .text(totalPages)
                .on('click', () => {
                    currentPage = totalPages;
                    loadData();
                });
            paginationControls.append(lastBtn);
        }
        
        // Next button
        const nextBtn = $('<button>')
            .addClass('pagination-btn')
            .text('Next')
            .prop('disabled', currentPage === totalPages)
            .on('click', () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    loadData();
                }
            });
        paginationControls.append(nextBtn);
    }
    
    
    function updateDashboardCards(stats) {
        $('#totalClients').text(stats.totalClients);
        $('#clientsChange').text(`+${stats.newClients} new this month`);

        const revenueChangeEl = $('#revenueChange');
        revenueChangeEl.text(`${Math.abs(stats.revenueChange)}% ${stats.revenueChange > 0 ? 'up' : 'down'} vs last month`)
                       .toggleClass('positive', stats.revenueChange > 0)
                       .toggleClass('negative', stats.revenueChange < 0);

        const outstandingChangeEl = $('#outstandingChange');
        outstandingChangeEl.text(`${Math.abs(stats.outstandingChange)}% ${stats.outstandingChange > 0 ? 'up' : 'down'} vs last month`)
                           .toggleClass('positive', stats.outstandingChange < 0) // Higher outstanding is bad
                           .toggleClass('negative', stats.outstandingChange > 0);

        clearInterval(dashboardStatsInterval);
        const currencies = ['RWF', 'USD', 'EUR'];
        let currencyIndex = 0;
        const cycle = () => {
            const currency = currencies[currencyIndex];
            const summary = stats.currencySummaries[currency];
            const revenueEl = $('#totalRevenue');
            const outstandingEl = $('#outstandingAmount');
            revenueEl.addClass('fading');
            outstandingEl.addClass('fading');
            setTimeout(() => {
                revenueEl.text(`${formatCurrency(summary.total_revenue)} ${currency}`).removeClass('fading');
                outstandingEl.text(`${formatCurrency(summary.outstanding_amount)} ${currency}`).removeClass('fading');
                currencyIndex = (currencyIndex + 1) % currencies.length;
            }, 400);
        };
        cycle();
        dashboardStatsInterval = setInterval(cycle, 5000);
    }

    function updateSummaryBarForFilteredView() {
        let totals = {};
        $('#clientTable tbody tr').each(function() {
            const currency = $(this).find('td').eq(7).text();
            if (!totals[currency]) {
                totals[currency] = { total: 0, paid: 0, due: 0 };
            }
            totals[currency].total += parseFloat($(this).find('td').eq(6).text().replace(/,/g, '')) || 0;
            totals[currency].paid += parseFloat($(this).find('td').eq(8).text().replace(/,/g, '')) || 0;
            totals[currency].due += parseFloat($(this).find('td').eq(9).text().replace(/,/g, '')) || 0;
        });

        const currencyKeys = Object.keys(totals);
        const summaryBar = $('#summaryBar');
        let html = '';
        if (currencyKeys.length === 1) {
            const currency = currencyKeys[0];
            html = `
                <div class="summary-item"><span class="label">Total:</span><span class="value">${formatCurrency(totals[currency].total)} ${currency}</span></div>
                <div class="summary-item"><span class="label">Paid:</span><span class="value">${formatCurrency(totals[currency].paid)} ${currency}</span></div>
                <div class="summary-item"><span class="label">Due:</span><span class="value">${formatCurrency(totals[currency].due)} ${currency}</span></div>
            `;
        } else {
            html = `<div class="summary-item"><span class="label">Totals (Mixed Currencies)</span></div>`;
        }
        summaryBar.html(html);
    }
    
    let summaryBarState = 0;
    function startSummaryBarCycler() {
        clearInterval(summaryBarInterval);
        const cycle = () => {
            const summaryBar = $('#summaryBar');
            summaryBar.removeClass('summary-rwf summary-usd summary-eur summary-current');
            let colorClass = 'summary-current';
            let html = '';

            if (summaryBarState < 3) {
                const currencies = ['RWF', 'USD', 'EUR'];
                const currency = currencies[summaryBarState];
                const totals = currencySummaries[currency] || { total_revenue: 0, outstanding_amount: 0 };
                const totalAmount = totals.total_revenue + totals.outstanding_amount;
                colorClass = `summary-${currency.toLowerCase()}`;
                html = `
                    <div class="summary-item"><span class="label">${currency} Total</span><span class="value">${formatCurrency(totalAmount)}</span></div>
                    <div class="summary-item"><span class="label">Paid</span><span class="value">${formatCurrency(totals.total_revenue)}</span></div>
                    <div class="summary-item"><span class="label">Due</span><span class="value">${formatCurrency(totals.outstanding_amount)}</span></div>
                `;
            } else {
                updateSummaryBarForFilteredView(); // This will regenerate the correct HTML
            }
            
            if (summaryBarState < 3) summaryBar.html(html);
            summaryBar.addClass(colorClass);
            summaryBarState = (summaryBarState + 1) % 4;
        };
        cycle(); // Initial call
        summaryBarInterval = setInterval(cycle, 7000);
    }

    function fetchForexRates() {
        $.ajax({
            url: 'https://open.er-api.com/v6/latest/USD',
            success: function(data) {
                if (data.result !== 'success') return;
                const forexList = $('.forex-list');
                let ratesHtml = '';
                ['RWF', 'EUR', 'GBP', 'KES', 'UGX', 'TZS'].forEach(currency => {
                    if (data.rates[currency]) {
                        ratesHtml += `<li><span class="currency-pair">USD/${currency}</span> <span class="rate">${data.rates[currency].toFixed(2)}</span></li>`;
                    }
                });
                forexList.html(ratesHtml.repeat(2));
            }
        });
    }

    function cancelEditing() {
        const editingRow = $('tr.editing-row');
        if (editingRow.length) {
            editingRow.replaceWith(editingRow.data('originalHTML'));
        }
    }

    // --- Event Handlers ---
    $('#avatar-button').on('click', (e) => { e.stopPropagation(); $('#dropdown-menu').toggleClass('show'); });
    $(document).on('click', (e) => { 
        if (!$(e.target).closest('.user-menu').length) $('#dropdown-menu').removeClass('show'); 
        if (!$(e.target).closest('.actions-menu').length) $('.actions-dropdown').removeClass('show');
    });
    $('#showAddFormBtn').on('click', () => $('#addClientCard').slideToggle(300));
    $('#addClientCard').on('input', '[name="amount"], [name="paid_amount"]', function() {
        const form = $(this).closest('form');
        const amount = parseFloat(form.find('[name="amount"]').val()) || 0;
        const paidAmount = parseFloat(form.find('[name="paid_amount"]').val()) || 0;
        form.find('[name="due_amount"]').val((amount - paidAmount).toFixed(2));
    });

    // TIN validation - only allow digits and max 9 characters
    $(document).on('input', '[name="TIN"]', function() {
        let value = $(this).val();
        // Remove non-digit characters
        value = value.replace(/\D/g, '');
        // Limit to 9 digits
        if (value.length > 9) {
            value = value.substring(0, 9);
        }
        $(this).val(value);
    });

    $('#clientForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate TIN if provided
        const tinValue = $('[name="TIN"]', this).val();
        if (tinValue && !/^\d{1,9}$/.test(tinValue)) {
            showToast('TIN must be up to 9 digits only', 'error');
            return;
        }
        
        showLoading(true);
        $.ajax({
            url: 'insert_client.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addClientCard').slideUp(300);
                    this.reset();
                    showToast('Client added successfully!');
                    loadData(); // Reload data after adding a client
                } else { handleAjaxError({responseJSON: response}, 'Failed to add client.'); }
            }.bind(this),
            error: (jqXHR) => handleAjaxError(jqXHR, 'Server error during insert.'),
            complete: () => showLoading(false)
        });
    });

    $('#clientTable').on('click', '.editBtn', function() {
        cancelEditing();
        const row = $(this).closest('tr');
        row.data('originalHTML', row[0].outerHTML);
        const cells = row.children('td');
        
        // Extract text content, handling nested elements
        const getCleanText = (cell) => {
            const $cell = $(cell);
            // For cells with multiple elements, get the main text
            const text = $cell.find('.truncate').text() || $cell.text();
            return text.trim();
        };
        
        const clientData = {
            reg_no: getCleanText(cells[2]),
            client_name: getCleanText(cells[3]),
            date: $(cells[4]).text().split('\n')[0].trim(), // Get date before relative date
            Responsible: $(cells[5]).find('.user-info span:last-child').text().trim() || $(cells[5]).text().trim(),
            TIN: $(cells[6]).text().trim(),
            service: $(cells[7]).text().trim(),
            amount: $(cells[8]).text().replace(/,/g, ''),
            currency: $(cells[9]).text().trim(),
            paid_amount: $(cells[10]).text().split('\n')[0].replace(/,/g, '') // Get amount before progress bar
        };
        
        const editRowHtml = `
            <td class="p-2">${$(cells[0]).html()}</td>
            <td class="p-2">${$(cells[1]).text()}</td>
            <td class="p-2"><input type="text" name="reg_no" class="form-control form-control-sm" value="${clientData.reg_no}"></td>
            <td class="p-2"><input type="text" name="client_name" class="form-control form-control-sm" value="${clientData.client_name}"></td>
            <td class="p-2"><input type="date" name="date" class="form-control form-control-sm" value="${clientData.date}"></td>
            <td class="p-2"><input type="text" name="Responsible" class="form-control form-control-sm" value="${clientData.Responsible}"></td>
            <td class="p-2"><input type="text" name="TIN" class="form-control form-control-sm" maxlength="9" pattern="[0-9]{1,9}" value="${clientData.TIN}"></td>
            <td class="p-2"><input type="text" name="service" class="form-control form-control-sm" value="${clientData.service}"></td>
            <td class="p-2"><input type="number" name="amount" class="form-control form-control-sm" step="0.01" value="${clientData.amount}"></td>
            <td class="p-2"><select name="currency" class="form-control form-control-sm form-select"><option value="RWF">RWF</option><option value="USD">USD</option><option value="EUR">EUR</option></select></td>
            <td class="p-2"><input type="number" name="paid_amount" class="form-control form-control-sm" step="0.01" value="${clientData.paid_amount}"></td>
            <td class="p-2" colspan="2"></td>
            <td class="p-2 action-buttons-cell"><button class="saveBtn btn btn-success btn-sm">Save</button><button class="cancelBtn btn btn-secondary btn-sm">Cancel</button></td>
        `;
        row.addClass('editing-row').html(editRowHtml);
        row.find('[name="currency"]').val(clientData.currency);
    });

    $('#clientTable').on('click', '.cancelBtn', cancelEditing);

    $('#clientTable').on('click', '.saveBtn', function() {
        const row = $(this).closest('tr');
        let formData = { id: row.data('id') };
        row.find('input, select').each(function() { formData[$(this).attr('name')] = $(this).val(); });
        showLoading(true);
        $.ajax({
            url: 'update_client.php', type: 'POST', data: formData, dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('Client updated successfully!');
                    loadData(); // Reload data after update
                } else { cancelEditing(); handleAjaxError({responseJSON: response}, 'Update failed.'); }
            },
            error: (jqXHR) => { cancelEditing(); handleAjaxError(jqXHR, 'Server error during update.'); },
            complete: () => showLoading(false)
        });
    });

    $('#clientTable').on('click', '.deleteBtn', function() {
        const clientId = $(this).closest('tr').data('id');
        showConfirm(() => {
            showLoading(true);
            $.ajax({
                url: 'delete_client.php', type: 'POST', data: { id: clientId }, dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('Client deleted.');
                        loadData(); // Reload data after delete
                    } else { handleAjaxError({responseJSON: response}, 'Could not delete client.'); }
                },
                error: (jqXHR) => handleAjaxError(jqXHR, 'Server error while deleting.'),
                complete: () => showLoading(false)
            });
        });
    });

    // --- Filter and Action Button Handlers ---
    let debounceTimer;
    $('#filterContainer input, #filterContainer select').on('change keyup', function() {
        clearTimeout(debounceTimer);
        
        // Special handling for entries per page - update immediately without debounce
        if ($(this).attr('id') === 'entriesPerPage') {
            const newPerPage = parseInt($(this).val());
            if (newPerPage !== perPage) {
                perPage = newPerPage;
                currentPage = 1; // Reset to first page when changing entries per page
                sessionStorage.setItem('clientsPerPage', perPage); // Persist selection
                loadData();
            }
            return;
        }
        
        currentPage = 1; // Reset to first page when filters change
        debounceTimer = setTimeout(() => loadData(), 400); // Call the main data load function on any filter change
    });
    
    $('#viewAllBtn').on('click', () => { 
        $('#filterContainer').find('input, select').not('#entriesPerPage').val(''); 
        currentPage = 1; // Reset to first page
        loadData(); // Reloads all data without filters
    });
    
    // --- Excel and Print Handlers ---
    $('#downloadExcelBtn').on('click', function() {
        const table = document.getElementById('clientTable');
        if (!table || table.rows.length <= 1) { showToast('No data to download.', 'error'); return; }
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Client_Data');
        XLSX.writeFile(wb, `Feza_Logistics_Clients_${new Date().toISOString().slice(0, 10)}.xlsx`);
    });

    $('#importExcelBtn').on('click', () => $('#importExcelFile').click());
    $('#importExcelFile').on('change', function(event) {
        const file = event.target.files[0];
        if (!file) return;
        showLoading(true);
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const records = XLSX.utils.sheet_to_json(XLSX.read(e.target.result, { type: 'binary' }).Sheets['Sheet1']);
                $.ajax({
                    url: 'import_excel.php', type: 'POST', contentType: 'application/json', data: JSON.stringify(records),
                    success: (response) => {
                        if (response.success) { showToast(response.message, 'success'); loadData(); } 
                        else { handleAjaxError({responseJSON: response}, 'Import failed.'); }
                    },
                    error: (jqXHR) => handleAjaxError(jqXHR, 'Server error during import.'),
                    complete: () => showLoading(false)
                });
            } catch (error) { showToast('Could not process Excel file.', 'error'); showLoading(false); }
        };
        reader.readAsBinaryString(file);
    });
    
    $('#printTableBtn').on('click', function() {
        const tableToPrint = $('#clientTable').clone();
        tableToPrint.find('.table-actions').remove(); // Remove actions column for printing
        const printWindow = window.open('', '', 'height=800,width=1200');
        printWindow.document.write('<html><head><title>Print Client Data</title><link rel="stylesheet" href="assets/css/design-system.css"><style>body{background:white;padding:2rem;}th:last-child,td:last-child{display:none;}</style></head><body><h1>Client Financial Data</h1>');
        printWindow.document.write(tableToPrint.prop('outerHTML'));
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => { printWindow.print(); printWindow.close(); }, 250);
    });

    // --- Modal and Table Action Handlers ---
    $('#clientTable').on('click', '.actions-menu-btn', function(e) {
        e.stopPropagation();
        $('.actions-dropdown').not($(this).next()).removeClass('show'); // Hide others
        $(this).next('.actions-dropdown').toggleClass('show');
    });

    $('#clientTable').on('click', '.print-link', function(e) { 
        e.preventDefault(); 
        
        // Check if the link is disabled
        if ($(this).hasClass('disabled')) {
            return false;
        }
        
        const docType = $(this).data('type'); 
        const clientId = $(this).closest('tr').data('id'); 
        $('#tin-clientId').val(clientId); 
        $('#tin-docType').val(docType); 
        $('#tinModal').addClass('show'); 
    });

    // Email document handler
    $('#clientTable').on('click', '.email-link', function(e) { 
        e.preventDefault(); 
        
        // Check if the link is disabled
        if ($(this).hasClass('disabled')) {
            return false;
        }
        
        const docType = $(this).data('type'); 
        const row = $(this).closest('tr');
        const clientId = row.data('id');
        const clientName = row.find('td').eq(2).text();
        
        // Open email modal
        if (typeof openEmailModal === 'function') {
            openEmailModal(docType, clientId, clientName, '');
        } else {
            showToast('Email functionality is loading. Please try again.', 'error');
        }
    });

    $('#clientTable').on('click', '.historyBtn', function(e) {
        e.preventDefault();
        const row = $(this).closest('tr');
        $('#historyModal-title').text(`History for: ${row.find('td').eq(2).text()}`);
        $('#historyModal-body').html('<p>Loading...</p>'); 
        $('#historyModal').addClass('show'); 
        $.ajax({ 
            url: 'fetch_history.php', data: { id: row.data('id') }, 
            success: (data) => { 
                let html = '<p>No history found.</p>'; 
                if (data.length) { 
                    html = '<table class="table"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead><tbody>'; 
                    data.forEach(item => { html += `<tr><td>${item.changed_at}</td><td>${item.user_name}</td><td>${item.action}</td><td>${item.details}</td></tr>`; }); 
                    html += '</tbody></table>'; 
                } 
                $('#historyModal-body').html(html); 
            } 
        }); 
    });

    $('#tinForm').on('submit', function(e) { e.preventDefault(); if (/^\d{9}$/.test($('#tin-number').val())) { window.open(`print_document.php?id=${$('#tin-clientId').val()}&type=${$('#tin-docType').val()}&tin=${$('#tin-number').val()}`, '_blank'); $('#tinModal').removeClass('show'); } else { $('.tin-error-message').text('Please enter exactly 9 digits.'); } });
    $('#closeTinModalBtn, #closeHistoryModalBtn').on('click', () => $('.enhanced-modal').removeClass('show'));

    // --- Quick Filter Chips Handler ---
    $('.filter-chip').on('click', function() {
        $('.filter-chip').removeClass('active');
        $(this).addClass('active');
        const status = $(this).data('status');
        $('#filterPaidStatus').val(status).trigger('change');
    });

    // --- Table Sorting Handler ---
    let currentSort = { column: '', direction: 'asc' };
    $('.sortable').on('click', function() {
        const column = $(this).data('column');
        
        // Toggle direction if clicking same column
        if (currentSort.column === column) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.direction = 'asc';
        }
        
        // Update visual indicators
        $('.sortable').removeClass('asc desc');
        $(this).addClass(currentSort.direction);
        
        // Sort the table rows
        const tbody = $('#clientTable tbody');
        const rows = tbody.find('tr').get();
        
        rows.sort(function(a, b) {
            const cellIndex = $(`.sortable[data-column="${column}"]`).index();
            let aVal = $(a).find('td').eq(cellIndex).text().trim();
            let bVal = $(b).find('td').eq(cellIndex).text().trim();
            
            // Handle numeric values
            if (column === 'amount' || column === 'paid_amount' || column === 'due_amount') {
                aVal = parseFloat(aVal.replace(/,/g, '')) || 0;
                bVal = parseFloat(bVal.replace(/,/g, '')) || 0;
            }
            // Handle dates
            else if (column === 'date') {
                aVal = new Date(aVal);
                bVal = new Date(bVal);
            }
            
            if (aVal < bVal) return currentSort.direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return currentSort.direction === 'asc' ? 1 : -1;
            return 0;
        });
        
        $.each(rows, function(index, row) {
            tbody.append(row);
            $(row).find('td').eq(1).text(index + 1); // Update row numbers
        });
    });

    // --- Bulk Selection Handlers ---
    $('#selectAllCheckbox').on('change', function() {
        const isChecked = $(this).prop('checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateBulkActionsBar();
    });

    $(document).on('change', '.row-checkbox', function() {
        updateBulkActionsBar();
        
        // Update select all checkbox state
        const totalCheckboxes = $('.row-checkbox').length;
        const checkedCheckboxes = $('.row-checkbox:checked').length;
        $('#selectAllCheckbox').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
    });

    function updateBulkActionsBar() {
        const selectedCount = $('.row-checkbox:checked').length;
        $('#selectedCount').text(selectedCount);
        
        if (selectedCount > 0) {
            $('#bulkActionsBar').addClass('show');
        } else {
            $('#bulkActionsBar').removeClass('show');
        }
    }

    $('#clearSelectionBtn').on('click', function() {
        $('.row-checkbox, #selectAllCheckbox').prop('checked', false);
        updateBulkActionsBar();
    });

    $('#bulkExportBtn').on('click', function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).data('id'));
        });
        
        if (selectedIds.length === 0) {
            showToast('No items selected', 'error');
            return;
        }
        
        showToast(`Exporting ${selectedIds.length} selected items...`);
        // Here you would implement the actual export logic
        // For now, we'll use the existing download functionality
        $('#downloadExcelBtn').trigger('click');
    });

    $('#bulkDeleteBtn').on('click', function() {
        const selectedIds = [];
        $('.row-checkbox:checked').each(function() {
            selectedIds.push($(this).data('id'));
        });
        
        if (selectedIds.length === 0) {
            showToast('No items selected', 'error');
            return;
        }
        
        showConfirm(() => {
            showLoading(true);
            let deleteCount = 0;
            let errorCount = 0;
            
            // Delete each selected item
            selectedIds.forEach((id, index) => {
                $.ajax({
                    url: 'delete_client.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            deleteCount++;
                        } else {
                            errorCount++;
                        }
                        
                        // Check if all deletions are complete
                        if (deleteCount + errorCount === selectedIds.length) {
                            showLoading(false);
                            if (deleteCount > 0) {
                                showToast(`${deleteCount} items deleted successfully`);
                                loadData();
                            }
                            if (errorCount > 0) {
                                showToast(`Failed to delete ${errorCount} items`, 'error');
                            }
                            updateBulkActionsBar();
                        }
                    },
                    error: function() {
                        errorCount++;
                        if (deleteCount + errorCount === selectedIds.length) {
                            showLoading(false);
                            showToast(`Failed to delete ${errorCount} items`, 'error');
                        }
                    }
                });
            });
        });
    });

    // --- Initial Load ---
    // Restore entries per page from sessionStorage if available
    const savedPerPage = sessionStorage.getItem('clientsPerPage');
    if (savedPerPage) {
        perPage = parseInt(savedPerPage);
        $('#entriesPerPage').val(perPage);
    }
    
    loadData(); // Initial data load
    fetchForexRates();
    setInterval(fetchForexRates, 1000 * 60 * 15);
});
</script>

<!-- AI Chat Assistant -->
<script src="assets/js/ai-chat.js"></script>

</body>
</html>