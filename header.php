<?php
// This file should be included at the top of every page.
// We start the session here to ensure it's available everywhere.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if the user is not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Load RBAC functions if available
if (file_exists(__DIR__ . '/rbac.php')) {
    require_once __DIR__ . '/rbac.php';
}

// Load Petty Cash RBAC functions if available
if (file_exists(__DIR__ . '/petty_cash_rbac.php')) {
    require_once __DIR__ . '/petty_cash_rbac.php';
}

// Get user's initials for the avatar
$username_for_avatar = $_SESSION['username'] ?? 'User';
$initials = strtoupper(substr($username_for_avatar, 0, 2));

// Get user roles and permissions
$userRoles = [];
$canManageRoles = false;
$canCreateInvoice = false;
$canCreateQuotation = false;
$canCreateReceipt = false;
$canManageSettings = false;
$canViewAuditLogs = false;
$hasPettyCashAccess = false;
$isPettyCashAdmin = false;
if (isset($_SESSION['user_id']) && function_exists('userHasPermission')) {
    $canManageRoles = userHasPermission($_SESSION['user_id'], 'manage-roles');
    $canCreateInvoice = userHasPermission($_SESSION['user_id'], 'create-invoice');
    $canCreateQuotation = userHasPermission($_SESSION['user_id'], 'create-quotation');
    $canCreateReceipt = userHasPermission($_SESSION['user_id'], 'create-receipt');
    $canManageSettings = userHasPermission($_SESSION['user_id'], 'manage-settings');
    $canViewAuditLogs = userHasPermission($_SESSION['user_id'], 'view-audit-logs');
    
    if (function_exists('getUserRoles')) {
        $userRoles = getUserRoles($_SESSION['user_id']);
    }
    
    // Check petty cash access
    if (function_exists('getUserPettyCashRoles')) {
        $pettyCashRoles = getUserPettyCashRoles($_SESSION['user_id']);
        $hasPettyCashAccess = !empty($pettyCashRoles);
        $isPettyCashAdmin = in_array('admin', $pettyCashRoles);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- The title will be set on each individual page -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <script src="assets/js/theme-toggle.js"></script>
    <style>
        /* Global Styles */
        :root {
            --primary-color: #0052cc; --primary-hover: #0041a3; --secondary-color: #f4f7f6; 
            --text-color: #333; --border-color: #dee2e6; --white-color: #fff;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--secondary-color);
            margin: 0;
            padding-top: 80px; /* Provide space for the fixed header */
        }
        /* Header Styles */
        .main-header {
            background-color: var(--white-color);
            border-bottom: 1px solid var(--border-color);
            padding: 0 40px;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .main-header .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        .user-menu {
            position: relative;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--white-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            cursor: pointer;
            user-select: none; /* Prevents text selection */
        }
        .dropdown-menu {
            display: none; /* Hidden by default */
            position: absolute;
            top: 55px;
            right: 0;
            background-color: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            width: 220px;
            overflow: hidden;
        }
        .dropdown-menu.show {
            display: block; /* Shown with JavaScript */
        }
        .dropdown-menu a {
            display: block;
            padding: 12px 20px;
            color: var(--text-color);
            text-decoration: none;
            font-size: 0.95rem;
        }
        .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }
        .dropdown-menu .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 8px 0;
        }
        .dropdown-menu .user-info {
            padding: 12px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid var(--border-color);
        }
        .dropdown-menu .user-info .user-name {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 4px;
        }
        .dropdown-menu .user-info .user-role {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .dropdown-menu .role-badge {
            display: inline-block;
            padding: 2px 8px;
            background-color: #e7f3ff;
            color: #0052cc;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-right: 4px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <a href="index.php" class="logo">Feza Logistics</a>
        <div style="display: flex; align-items: center; gap: 15px;">
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme" title="Toggle dark mode">
                <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 18C8.68629 18 6 15.3137 6 12C6 8.68629 8.68629 6 12 6C15.3137 6 18 8.68629 18 12C18 15.3137 15.3137 18 12 18ZM12 16C14.2091 16 16 14.2091 16 12C16 9.79086 14.2091 8 12 8C9.79086 8 8 9.79086 8 12C8 14.2091 9.79086 16 12 16ZM11 1H13V4H11V1ZM11 20H13V23H11V20ZM3.51472 4.92893L4.92893 3.51472L7.05025 5.63604L5.63604 7.05025L3.51472 4.92893ZM16.9497 18.364L18.364 16.9497L20.4853 19.0711L19.0711 20.4853L16.9497 18.364ZM19.0711 3.51472L20.4853 4.92893L18.364 7.05025L16.9497 5.63604L19.0711 3.51472ZM5.63604 16.9497L7.05025 18.364L4.92893 20.4853L3.51472 19.0711L5.63604 16.9497ZM23 11V13H20V11H23ZM4 11V13H1V11H4Z"/></svg>
                <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 7C10 10.866 13.134 14 17 14C18.9584 14 20.729 13.1957 21.9995 11.8995C22 11.933 22 11.9665 22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C12.0335 2 12.067 2 12.1005 2.00049C10.8043 3.27098 10 5.04157 10 7ZM4 12C4 16.4183 7.58172 20 12 20C15.0583 20 17.7158 18.2839 19.062 15.7621C18.3945 15.9187 17.7035 16 17 16C12.0294 16 8 11.9706 8 7C8 6.29648 8.08133 5.60547 8.2379 4.938C5.71611 6.28423 4 8.9417 4 12Z"/></svg>
            </button>
            <div class="user-menu">
                <div class="user-avatar" id="avatar-button"><?php echo htmlspecialchars($initials); ?></div>
                <div class="dropdown-menu" id="dropdown-menu">
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($username_for_avatar); ?></div>
                    <div class="user-role">
                        <?php if (!empty($userRoles)): ?>
                            <?php foreach ($userRoles as $role): ?>
                                <span class="role-badge"><?php echo htmlspecialchars($role['name']); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="role-badge">No Role Assigned</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="profile.php">Manage Profile</a>
                <a href="document_list.php">My Documents</a>
                <a href="doc_generator.php">Doc Generator</a>
                <a href="transactions.php">Transactions</a>
                <a href="petty_cash.php">Petty Cash</a>
                <a href="analytics_dashboard.php">📊 Analytics</a>
                <a href="vendors.php">👥 Vendors</a>
                <a href="api/documentation.php">📚 API Docs</a>
                <?php if ($hasPettyCashAccess): ?>
                    <a href="petty_cash_analytics.php" style="padding-left: 30px; font-size: 0.9rem;">↳ Analytics</a>
                    <?php if (function_exists('canPerformPettyCashAction') && canPerformPettyCashAction($_SESSION['user_id'], 'approve')): ?>
                        <a href="petty_cash_approvals.php" style="padding-left: 30px; font-size: 0.9rem;">↳ Approvals</a>
                    <?php endif; ?>
                    <?php if ($isPettyCashAdmin): ?>
                        <a href="petty_cash_categories.php" style="padding-left: 30px; font-size: 0.9rem;">↳ Categories</a>
                    <?php endif; ?>
                    <?php if (function_exists('canPerformPettyCashAction') && canPerformPettyCashAction($_SESSION['user_id'], 'reconcile')): ?>
                        <a href="petty_cash_reconciliation.php" style="padding-left: 30px; font-size: 0.9rem;">↳ Reconciliation</a>
                    <?php endif; ?>
                    <?php if (function_exists('canPerformPettyCashAction') && canPerformPettyCashAction($_SESSION['user_id'], 'replenish')): ?>
                        <a href="petty_cash_replenishment.php" style="padding-left: 30px; font-size: 0.9rem;">↳ Replenishment</a>
                    <?php endif; ?>
                    <?php if ($isPettyCashAdmin): ?>
                        <a href="petty_cash_roles.php" style="padding-left: 30px; font-size: 0.9rem;">↳ Roles</a>
                        <a href="petty_cash_settings.php" style="padding-left: 30px; font-size: 0.9rem;">↳ Settings</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($canManageRoles || $canManageSettings || $canViewAuditLogs): ?>
                    <div class="divider"></div>
                    <?php if ($canManageRoles): ?>
                        <a href="manage_roles.php">Role Management</a>
                    <?php endif; ?>
                    <?php if ($canManageSettings): ?>
                        <a href="manage_settings.php">Settings</a>
                    <?php endif; ?>
                    <?php if ($canViewAuditLogs): ?>
                        <a href="view_activity_logs.php">Activity Logs</a>
                    <?php endif; ?>
                    <?php if ($canManageRoles): ?>
                        <a href="security_dashboard.php">Security Dashboard</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($canCreateQuotation || $canCreateInvoice || $canCreateReceipt): ?>
                    <div class="divider"></div>
                    <?php if ($canCreateQuotation): ?>
                        <a href="create_quotation.php">Create Quotation</a>
                    <?php endif; ?>
                    <?php if ($canCreateInvoice): ?>
                        <a href="create_invoice.php">Create Invoice</a>
                    <?php endif; ?>
                    <?php if ($canCreateReceipt): ?>
                        <a href="create_receipt.php">Create Receipt</a>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="divider"></div>
                <a href="reports/profit_loss.php">📈 P&L Report</a>
                <div class="divider"></div>
                <a href="logout.php">Logout</a>
            </div>
            </div>
        </div>
    </header>

    <main class="page-content">
        <!-- The content of each page will go here -->

    <script>
        // JavaScript for the dropdown menu
        document.addEventListener('DOMContentLoaded', function() {
            const avatarButton = document.getElementById('avatar-button');
            const dropdownMenu = document.getElementById('dropdown-menu');

            if (avatarButton) {
                avatarButton.addEventListener('click', function(event) {
                    event.stopPropagation(); // Prevent the click from closing the menu immediately
                    dropdownMenu.classList.toggle('show');
                });
            }

            // Close the dropdown if the user clicks outside of it
            window.addEventListener('click', function(event) {
                if (dropdownMenu && !dropdownMenu.contains(event.target) && !avatarButton.contains(event.target)) {
                    if (dropdownMenu.classList.contains('show')) {
                        dropdownMenu.classList.remove('show');
                    }
                }
            });
        });
    </script>
</body> <!-- The body and html tags will be closed by a footer.php file -->