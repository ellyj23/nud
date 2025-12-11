<?php
/**
 * Inventory Products Management
 * Manage product catalog and stock levels
 */

require_once '../header.php';
require_once '../db.php';

// Fetch all products
$products = [];
try {
    $stmt = $pdo->query("
        SELECT 
            p.*,
            CASE 
                WHEN p.current_stock <= p.reorder_level THEN 'low'
                WHEN p.current_stock = 0 THEN 'out'
                ELSE 'ok'
            END as stock_status
        FROM inventory_products p
        ORDER BY p.created_at DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Failed to fetch products: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Feza Logistics</title>
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
    <style>
        .inventory-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .inventory-header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .btn-add {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .products-table {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: var(--secondary-color);
            padding: 12px 16px;
            text-align: left;
            color: var(--text-color);
            font-weight: 600;
        }
        
        table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }
        
        table tbody tr:hover {
            background: var(--secondary-color);
        }
        
        .stock-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .stock-status.ok {
            background: #d1fae5;
            color: #065f46;
        }
        
        .stock-status.low {
            background: #fef3c7;
            color: #92400e;
        }
        
        .stock-status.out {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-color);
        }
    </style>
</head>
<body>
    <div class="inventory-container">
        <div class="inventory-header">
            <h1>📦 Inventory Management</h1>
            <a href="add_product.php" class="btn-add">
                <span>➕</span>
                Add Product
            </a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($products); ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php echo count(array_filter($products, fn($p) => $p['stock_status'] === 'low' || $p['stock_status'] === 'out')); ?>
                </div>
                <div class="stat-label">Low Stock Alerts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php echo count(array_filter($products, fn($p) => $p['status'] === 'active')); ?>
                </div>
                <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php 
                        $totalValue = array_sum(array_map(fn($p) => $p['current_stock'] * $p['unit_cost'], $products));
                        echo number_format($totalValue, 0);
                    ?>
                </div>
                <div class="stat-label">Total Inventory Value (RWF)</div>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (empty($products)): ?>
            <div class="no-products">
                <p>No products found</p>
                <a href="add_product.php" class="btn-add">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Unit Cost</th>
                            <th>Selling Price</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo number_format($product['current_stock'], 2); ?> 
                                    <?php echo htmlspecialchars($product['unit_of_measure']); ?>
                                </td>
                                <td><?php echo number_format($product['unit_cost'], 2); ?></td>
                                <td><?php echo number_format($product['selling_price'], 2); ?></td>
                                <td>
                                    <?php if ($product['stock_status'] === 'out'): ?>
                                        <span class="stock-status out">Out of Stock</span>
                                    <?php elseif ($product['stock_status'] === 'low'): ?>
                                        <span class="stock-status low">Low Stock</span>
                                    <?php else: ?>
                                        <span class="stock-status ok">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                       style="color: var(--primary-color); text-decoration: none;">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
