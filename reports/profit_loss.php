<?php
/**
 * Profit & Loss Statement
 * Generate comprehensive P&L reports with comparison capabilities
 */

require_once '../header.php';
require_once '../db.php';

// Get date range from query parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-d'); // Today
$compareMode = $_GET['compare'] ?? 'none'; // none, previous_period, previous_year

// Calculate comparison period
$compStartDate = $startDate;
$compEndDate = $endDate;

if ($compareMode === 'previous_period') {
    $days = (strtotime($endDate) - strtotime($startDate)) / 86400;
    $compEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
    $compStartDate = date('Y-m-d', strtotime($compEndDate . ' -' . $days . ' days'));
} elseif ($compareMode === 'previous_year') {
    $compStartDate = date('Y-m-d', strtotime($startDate . ' -1 year'));
    $compEndDate = date('Y-m-d', strtotime($endDate . ' -1 year'));
}

try {
    // Revenue
    $stmt = $pdo->prepare("
        SELECT 
            SUM(amount) as total,
            currency
        FROM transactions
        WHERE transaction_type = 'credit'
        AND created_at BETWEEN ? AND ?
        GROUP BY currency
    ");
    $stmt->execute([$startDate, $endDate]);
    $revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Expenses (from petty cash)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(amount) as total,
            currency,
            category
        FROM petty_cash
        WHERE transaction_type = 'expense'
        AND created_at BETWEEN ? AND ?
        GROUP BY currency, category
    ");
    $stmt->execute([$startDate, $endDate]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Comparison period data if needed
    $compRevenue = [];
    $compExpenses = [];
    
    if ($compareMode !== 'none') {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(amount) as total,
                currency
            FROM transactions
            WHERE transaction_type = 'credit'
            AND created_at BETWEEN ? AND ?
            GROUP BY currency
        ");
        $stmt->execute([$compStartDate, $compEndDate]);
        $compRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT 
                SUM(amount) as total,
                currency,
                category
            FROM petty_cash
            WHERE transaction_type = 'expense'
            AND created_at BETWEEN ? AND ?
            GROUP BY currency, category
        ");
        $stmt->execute([$compStartDate, $compEndDate]);
        $compExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = "Failed to generate report: " . $e->getMessage();
}

// Helper function to sum amounts by currency
function sumByCurrency($data) {
    $result = [];
    foreach ($data as $item) {
        $currency = $item['currency'] ?? 'RWF';
        if (!isset($result[$currency])) {
            $result[$currency] = 0;
        }
        $result[$currency] += floatval($item['total']);
    }
    return $result;
}

$revenueTotal = sumByCurrency($revenue);
$expensesByCategory = [];
foreach ($expenses as $expense) {
    $category = $expense['category'] ?? 'Uncategorized';
    if (!isset($expensesByCategory[$category])) {
        $expensesByCategory[$category] = [];
    }
    $currency = $expense['currency'] ?? 'RWF';
    if (!isset($expensesByCategory[$category][$currency])) {
        $expensesByCategory[$category][$currency] = 0;
    }
    $expensesByCategory[$category][$currency] += floatval($expense['total']);
}

$expensesTotal = sumByCurrency($expenses);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement - Feza Logistics</title>
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        .report-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .report-header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .report-period {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .report-controls {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .control-group {
            flex: 1;
            min-width: 200px;
        }
        
        .control-group label {
            display: block;
            color: var(--text-color);
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .control-group input,
        .control-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--white-color);
            color: var(--text-color);
        }
        
        .btn-generate {
            padding: 10px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-export {
            padding: 10px 24px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .report-table {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .report-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .report-table th {
            background: var(--secondary-color);
            padding: 12px 16px;
            text-align: left;
            color: var(--text-color);
            font-weight: 600;
        }
        
        .report-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        .report-table tr.section-header {
            background: var(--secondary-color);
            font-weight: 600;
        }
        
        .report-table tr.total-row {
            background: #f0f9ff;
            font-weight: 700;
        }
        
        [data-theme="dark"] .report-table tr.total-row {
            background: #1e3a8a;
        }
        
        .report-table tr.net-profit {
            background: #d1fae5;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        [data-theme="dark"] .report-table tr.net-profit {
            background: #065f46;
        }
        
        .amount {
            text-align: right;
            font-family: monospace;
        }
        
        .indent-1 {
            padding-left: 32px !important;
        }
        
        .print-only {
            display: none;
        }
        
        @media print {
            .report-controls, .btn-export {
                display: none;
            }
            .print-only {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <h1>📊 Profit & Loss Statement</h1>
            <p class="report-period">
                Period: <?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?>
            </p>
            <?php if ($compareMode !== 'none'): ?>
                <p class="report-period">
                    Compared to: <?php echo date('M d, Y', strtotime($compStartDate)); ?> - <?php echo date('M d, Y', strtotime($compEndDate)); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="report-controls">
            <div class="control-group">
                <label>Start Date</label>
                <input type="date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            <div class="control-group">
                <label>End Date</label>
                <input type="date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            <div class="control-group">
                <label>Compare To</label>
                <select id="compare">
                    <option value="none" <?php echo $compareMode === 'none' ? 'selected' : ''; ?>>No Comparison</option>
                    <option value="previous_period" <?php echo $compareMode === 'previous_period' ? 'selected' : ''; ?>>Previous Period</option>
                    <option value="previous_year" <?php echo $compareMode === 'previous_year' ? 'selected' : ''; ?>>Previous Year</option>
                </select>
            </div>
            <button class="btn-generate" onclick="generateReport()">Generate</button>
            <button class="btn-export" onclick="exportToPDF()">Export PDF</button>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="report-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%;">Account</th>
                        <?php foreach (array_keys($revenueTotal) as $currency): ?>
                            <th class="amount"><?php echo htmlspecialchars($currency); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Revenue Section -->
                    <tr class="section-header">
                        <td colspan="<?php echo count($revenueTotal) + 1; ?>">REVENUE</td>
                    </tr>
                    <tr>
                        <td class="indent-1">Sales Revenue</td>
                        <?php foreach (array_keys($revenueTotal) as $currency): ?>
                            <td class="amount"><?php echo number_format($revenueTotal[$currency], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="total-row">
                        <td>Total Revenue</td>
                        <?php foreach (array_keys($revenueTotal) as $currency): ?>
                            <td class="amount"><?php echo number_format($revenueTotal[$currency], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Expenses Section -->
                    <tr class="section-header">
                        <td colspan="<?php echo count($revenueTotal) + 1; ?>">EXPENSES</td>
                    </tr>
                    <?php foreach ($expensesByCategory as $category => $amounts): ?>
                        <tr>
                            <td class="indent-1"><?php echo htmlspecialchars($category); ?></td>
                            <?php foreach (array_keys($revenueTotal) as $currency): ?>
                                <td class="amount"><?php echo number_format($amounts[$currency] ?? 0, 2); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td>Total Expenses</td>
                        <?php foreach (array_keys($revenueTotal) as $currency): ?>
                            <td class="amount"><?php echo number_format($expensesTotal[$currency] ?? 0, 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Net Profit -->
                    <tr class="net-profit">
                        <td>NET PROFIT / (LOSS)</td>
                        <?php foreach (array_keys($revenueTotal) as $currency): ?>
                            <?php 
                                $netProfit = $revenueTotal[$currency] - ($expensesTotal[$currency] ?? 0);
                            ?>
                            <td class="amount"><?php echo number_format($netProfit, 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function generateReport() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const compare = document.getElementById('compare').value;
            
            window.location.href = `profit_loss.php?start_date=${startDate}&end_date=${endDate}&compare=${compare}`;
        }
        
        function exportToPDF() {
            window.print();
        }
    </script>
</body>
</html>
