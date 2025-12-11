<?php
/**
 * Add Vendor Page
 * Form to create a new vendor/supplier
 */

require_once 'header.php';
require_once 'db.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendorName = trim($_POST['vendor_name'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $tin = trim($_POST['tin'] ?? '');
    $bankAccount = trim($_POST['bank_account'] ?? '');
    $bankName = trim($_POST['bank_name'] ?? '');
    $paymentTerms = $_POST['payment_terms'] ?? 'Net 30';
    $currency = $_POST['currency'] ?? 'RWF';
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($vendorName)) {
        $error = 'Vendor name is required';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO vendors (
                    vendor_name, contact_person, email, phone, address, tin,
                    bank_account, bank_name, payment_terms, currency, notes, created_by
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $vendorName, $contactPerson, $email, $phone, $address, $tin,
                $bankAccount, $bankName, $paymentTerms, $currency, $notes,
                $_SESSION['user_id']
            ]);
            
            $success = 'Vendor created successfully!';
            
            // Redirect after 2 seconds
            header('Refresh: 2; URL=vendors.php');
        } catch (PDOException $e) {
            $error = 'Failed to create vendor: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vendor - Feza Logistics</title>
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-header {
            margin-bottom: 30px;
        }
        
        .form-header h1 {
            color: var(--text-color);
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .form-card {
            background: var(--white-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-group label .required {
            color: #dc3545;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--white-color);
            color: var(--text-color);
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }
        
        .btn-secondary:hover {
            background: #dee2e6;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #dc2626;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>➕ Add New Vendor</h1>
            <p style="color: var(--text-secondary);">Enter vendor/supplier information</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            Vendor Name <span class="required">*</span>
                        </label>
                        <input type="text" name="vendor_name" required 
                               value="<?php echo htmlspecialchars($_POST['vendor_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" 
                               value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>TIN Number</label>
                        <input type="text" name="tin" 
                               value="<?php echo htmlspecialchars($_POST['tin'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Terms</label>
                        <select name="payment_terms">
                            <option value="Net 15">Net 15 Days</option>
                            <option value="Net 30" selected>Net 30 Days</option>
                            <option value="Net 45">Net 45 Days</option>
                            <option value="Net 60">Net 60 Days</option>
                            <option value="Cash">Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Bank Account</label>
                        <input type="text" name="bank_account" 
                               value="<?php echo htmlspecialchars($_POST['bank_account'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Bank Name</label>
                        <input type="text" name="bank_name" 
                               value="<?php echo htmlspecialchars($_POST['bank_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Currency</label>
                        <select name="currency">
                            <option value="RWF" selected>RWF - Rwandan Franc</option>
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="GBP">GBP - British Pound</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="vendors.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Vendor</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
