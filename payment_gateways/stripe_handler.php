<?php
/**
 * Stripe Payment Gateway Handler
 * Handles Stripe payment processing and webhooks
 * 
 * NOTE: This is a basic implementation. For production use:
 * 1. Install Stripe PHP SDK: composer require stripe/stripe-php
 * 2. Store API keys securely in environment variables
 * 3. Implement proper error handling and logging
 * 4. Add webhook signature verification
 */

session_start();
require_once '../db.php';

/**
 * Initialize Stripe
 * In production, use: require_once 'vendor/autoload.php';
 */
function initStripe() {
    // This is a placeholder. In production, you would:
    // \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
    
    // For now, get from database
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT secret_key, is_test_mode 
            FROM payment_gateways 
            WHERE gateway_type = 'stripe' AND is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            throw new Exception('Stripe gateway not configured or inactive');
        }
        
        return $config;
    } catch (PDOException $e) {
        throw new Exception('Failed to load Stripe configuration');
    }
}

/**
 * Create Payment Intent
 * Creates a Stripe payment intent for an invoice
 */
function createPaymentIntent($invoiceId, $amount, $currency = 'rwf') {
    global $pdo;
    
    try {
        $config = initStripe();
        
        // Get invoice details
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as client_name, c.email as client_email
            FROM transactions i
            LEFT JOIN clients c ON i.client_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception('Invoice not found');
        }
        
        // Generate unique transaction reference
        $transactionRef = 'PAY-' . strtoupper(uniqid());
        
        // In production, you would create actual Stripe payment intent:
        /*
        $intent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100, // Convert to cents
            'currency' => $currency,
            'metadata' => [
                'invoice_id' => $invoiceId,
                'transaction_ref' => $transactionRef
            ],
            'receipt_email' => $invoice['client_email']
        ]);
        */
        
        // For demonstration, create a mock payment intent
        $mockIntent = [
            'id' => 'pi_' . uniqid(),
            'client_secret' => 'pi_' . uniqid() . '_secret_' . uniqid(),
            'status' => 'requires_payment_method',
            'amount' => $amount * 100,
            'currency' => $currency
        ];
        
        // Store payment transaction in database
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions (
                gateway_id, invoice_id, client_id, transaction_ref,
                gateway_transaction_id, amount, currency, status,
                customer_email, metadata
            )
            SELECT 
                id, ?, ?, ?, ?, ?, ?, 'pending', ?, ?
            FROM payment_gateways 
            WHERE gateway_type = 'stripe' AND is_active = TRUE
            LIMIT 1
        ");
        
        $metadata = json_encode([
            'payment_intent_id' => $mockIntent['id'],
            'client_secret' => $mockIntent['client_secret']
        ]);
        
        $stmt->execute([
            $invoiceId,
            $invoice['client_id'],
            $transactionRef,
            $mockIntent['id'],
            $amount,
            $currency,
            $invoice['client_email'],
            $metadata
        ]);
        
        return [
            'success' => true,
            'transaction_ref' => $transactionRef,
            'payment_intent' => $mockIntent
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Process Webhook
 * Handles Stripe webhook events
 */
function processWebhook() {
    global $pdo;
    
    try {
        // Get webhook payload
        $payload = @file_get_contents('php://input');
        $event = json_decode($payload, true);
        
        // In production, verify webhook signature:
        /*
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $webhook_secret = getenv('STRIPE_WEBHOOK_SECRET');
        
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $webhook_secret
            );
        } catch(\UnexpectedValueException $e) {
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            exit();
        }
        */
        
        // Handle different event types
        switch ($event['type']) {
            case 'payment_intent.succeeded':
                handlePaymentSuccess($event['data']['object']);
                break;
                
            case 'payment_intent.payment_failed':
                handlePaymentFailure($event['data']['object']);
                break;
                
            case 'charge.refunded':
                handleRefund($event['data']['object']);
                break;
        }
        
        http_response_code(200);
        echo json_encode(['received' => true]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Handle successful payment
 */
function handlePaymentSuccess($paymentIntent) {
    global $pdo;
    
    try {
        // Update payment transaction
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'completed', processed_at = NOW()
            WHERE gateway_transaction_id = ?
        ");
        $stmt->execute([$paymentIntent['id']]);
        
        // Get invoice ID from payment transaction
        $stmt = $pdo->prepare("
            SELECT invoice_id, amount 
            FROM payment_transactions 
            WHERE gateway_transaction_id = ?
        ");
        $stmt->execute([$paymentIntent['id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($payment && $payment['invoice_id']) {
            // Update invoice status
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'paid', payment_date = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$payment['invoice_id']]);
            
            // Log activity
            if (function_exists('logActivity')) {
                logActivity(
                    $_SESSION['user_id'] ?? null,
                    'payment_received',
                    'invoice',
                    $payment['invoice_id'],
                    "Payment received via Stripe: " . $payment['amount']
                );
            }
        }
        
    } catch (PDOException $e) {
        error_log('Stripe webhook error: ' . $e->getMessage());
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailure($paymentIntent) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'failed', 
                error_message = ?,
                processed_at = NOW()
            WHERE gateway_transaction_id = ?
        ");
        $stmt->execute([
            $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
            $paymentIntent['id']
        ]);
    } catch (PDOException $e) {
        error_log('Stripe webhook error: ' . $e->getMessage());
    }
}

/**
 * Handle refund
 */
function handleRefund($charge) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE payment_transactions 
            SET status = 'refunded', processed_at = NOW()
            WHERE gateway_transaction_id = ?
        ");
        $stmt->execute([$charge['payment_intent']]);
    } catch (PDOException $e) {
        error_log('Stripe webhook error: ' . $e->getMessage());
    }
}

// Handle webhook requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/webhook') !== false) {
    processWebhook();
    exit;
}

// Handle API requests
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'create_payment_intent':
            $invoiceId = $_POST['invoice_id'] ?? 0;
            $amount = $_POST['amount'] ?? 0;
            $currency = $_POST['currency'] ?? 'rwf';
            
            $result = createPaymentIntent($invoiceId, $amount, $currency);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
