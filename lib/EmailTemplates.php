<?php
/**
 * EmailTemplates.php
 * 
 * Centralized email template system for Feza Logistics
 * Provides professional, responsive HTML email templates for all document types
 * 
 * @package FezaLogistics
 * @since 1.0
 */

class EmailTemplates {
    
    // Company information
    const COMPANY_NAME = 'Feza Logistics Ltd';
    const COMPANY_ADDRESS = 'KN 5 Rd, KG 16 AVe 31, Kigali International Airport, Rwanda';
    const COMPANY_TIN = '121933433';
    const COMPANY_PHONE = '(+250) 788 616 117';
    const COMPANY_EMAIL = 'info@fezalogistics.com';
    const COMPANY_WEBSITE = 'www.fezalogistics.com';
    
    // Brand colors
    const PRIMARY_COLOR = '#0052cc';
    const PRIMARY_DARK = '#003d99';
    const SUCCESS_COLOR = '#10b981';
    const WARNING_COLOR = '#f59e0b';
    const DANGER_COLOR = '#ef4444';
    const TEXT_COLOR = '#333333';
    const TEXT_MUTED = '#6b7280';
    const BORDER_COLOR = '#e5e7eb';
    const BG_LIGHT = '#f9fafb';
    
    /**
     * Get base email styles
     * 
     * @return string CSS styles for email templates
     */
    private static function getBaseStyles(): string {
        return '
            /* Reset and base styles */
            body, table, td, p, a { 
                font-family: Arial, Helvetica, sans-serif;
                -webkit-text-size-adjust: 100%;
                -ms-text-size-adjust: 100%;
            }
            table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
            img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
            body { margin: 0; padding: 0; width: 100%; background-color: #f4f4f4; }
            
            /* Container */
            .email-container { max-width: 600px; margin: 0 auto; background: white; }
            
            /* Header */
            .email-header {
                background: linear-gradient(135deg, ' . self::PRIMARY_COLOR . ' 0%, ' . self::PRIMARY_DARK . ' 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }
            .email-header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 700;
            }
            .email-header .logo-icon {
                font-size: 40px;
                margin-bottom: 10px;
            }
            
            /* Content */
            .email-content {
                padding: 30px;
                color: ' . self::TEXT_COLOR . ';
                line-height: 1.6;
            }
            .email-content p { margin: 0 0 15px 0; }
            
            /* Message box */
            .message-box {
                background: ' . self::BG_LIGHT . ';
                border-left: 4px solid ' . self::PRIMARY_COLOR . ';
                padding: 15px;
                margin: 20px 0;
                white-space: pre-wrap;
            }
            
            /* Document info */
            .document-info {
                background: #eff6ff;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
            }
            .document-info h3 {
                margin: 0 0 15px 0;
                color: ' . self::PRIMARY_COLOR . ';
                font-size: 16px;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid ' . self::BORDER_COLOR . ';
            }
            .info-row:last-child { border-bottom: none; }
            .info-label {
                font-weight: bold;
                color: #4b5563;
            }
            
            /* Summary table */
            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            .summary-table th, .summary-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid ' . self::BORDER_COLOR . ';
            }
            .summary-table th {
                background: ' . self::BG_LIGHT . ';
                font-weight: 600;
                color: #374151;
            }
            .summary-table .amount { text-align: right; font-family: monospace; }
            .summary-table .total-row {
                font-weight: bold;
                background: #f3f4f6;
            }
            
            /* Buttons */
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: ' . self::PRIMARY_COLOR . ';
                color: white !important;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                margin: 10px 0;
            }
            .btn:hover { background: ' . self::PRIMARY_DARK . '; }
            
            /* Footer */
            .email-footer {
                background: ' . self::BG_LIGHT . ';
                padding: 25px 30px;
                text-align: center;
                color: ' . self::TEXT_MUTED . ';
                font-size: 12px;
                border-top: 1px solid ' . self::BORDER_COLOR . ';
            }
            .email-footer p { margin: 5px 0; }
            .email-footer .company-name { font-weight: bold; color: ' . self::TEXT_COLOR . '; }
            .email-footer .disclaimer {
                margin-top: 15px;
                color: #9ca3af;
                font-style: italic;
            }
            
            /* Status badges */
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-paid { background: #d1fae5; color: #065f46; }
            .status-pending { background: #fef3c7; color: #92400e; }
            .status-unpaid { background: #fee2e2; color: #991b1b; }
        ';
    }
    
    /**
     * Get email header HTML
     * 
     * @param string $title Header title
     * @param string $icon Header icon (emoji)
     * @return string HTML for email header
     */
    private static function getHeader(string $title, string $icon = '📄'): string {
        return '
            <div class="email-header">
                <div class="logo-icon">' . $icon . '</div>
                <h1>' . htmlspecialchars($title) . '</h1>
            </div>
        ';
    }
    
    /**
     * Get email footer HTML
     * 
     * @return string HTML for email footer
     */
    private static function getFooter(): string {
        return '
            <div class="email-footer">
                <p class="company-name">' . self::COMPANY_NAME . '</p>
                <p>' . self::COMPANY_ADDRESS . '</p>
                <p>TIN: ' . self::COMPANY_TIN . ' | Phone: ' . self::COMPANY_PHONE . '</p>
                <p>Email: ' . self::COMPANY_EMAIL . ' | Web: ' . self::COMPANY_WEBSITE . '</p>
                <p class="disclaimer">This is an automated message from Feza Logistics Financial Management System. Please do not reply directly to this email.</p>
            </div>
        ';
    }
    
    /**
     * Wrap content in full email template
     * 
     * @param string $headerTitle Header title
     * @param string $headerIcon Header icon
     * @param string $bodyContent Body content HTML
     * @return string Complete HTML email
     */
    private static function wrapTemplate(string $headerTitle, string $headerIcon, string $bodyContent): string {
        $styles = self::getBaseStyles();
        $header = self::getHeader($headerTitle, $headerIcon);
        $footer = self::getFooter();
        
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($headerTitle) . '</title>
    <style>' . $styles . '</style>
</head>
<body style="margin: 0; padding: 20px; background-color: #f4f4f4;">
    <div class="email-container" style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        ' . $header . '
        <div class="email-content">
            ' . $bodyContent . '
        </div>
        ' . $footer . '
    </div>
</body>
</html>';
    }
    
    /**
     * Generate Invoice email template
     * 
     * @param array $data Invoice data containing:
     *   - recipient_name: Name of the recipient
     *   - doc_id: Invoice document ID
     *   - sender_name: Name of the sender
     *   - message: Custom message body
     *   - amount: Invoice amount (optional)
     *   - currency: Currency code (default: RWF)
     *   - due_date: Payment due date (optional)
     *   - status: Payment status (paid, partially-paid, pending, unpaid, overdue, draft)
     * @return string HTML email content
     */
    public static function invoiceEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'Valued Customer');
        $docId = htmlspecialchars($data['doc_id'] ?? '');
        $senderName = htmlspecialchars($data['sender_name'] ?? 'Feza Logistics');
        $message = htmlspecialchars($data['message'] ?? '');
        $amount = htmlspecialchars($data['amount'] ?? '');
        $currency = htmlspecialchars($data['currency'] ?? 'RWF');
        $dueDate = htmlspecialchars($data['due_date'] ?? '');
        $status = strtolower($data['status'] ?? 'pending');
        
        // Map status to CSS class - handles various status values gracefully
        $statusClass = match($status) {
            'paid', 'completed' => 'status-paid',
            'partially-paid', 'partial', 'pending' => 'status-pending',
            default => 'status-unpaid'
        };
        $statusLabel = ucwords(str_replace('-', ' ', $status));
        
        $bodyContent = '
            <p>Dear <strong>' . $recipientName . '</strong>,</p>
            
            <div class="message-box">' . nl2br($message) . '</div>
            
            <div class="document-info">
                <h3>📋 Invoice Details</h3>
                <table style="width: 100%;">
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Invoice Number:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;"><strong>#' . $docId . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Date:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . date('F j, Y') . '</td>
                    </tr>
                    ' . ($dueDate ? '<tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Due Date:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . $dueDate . '</td>
                    </tr>' : '') . '
                    ' . ($amount ? '<tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Amount:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;"><strong>' . $currency . ' ' . $amount . '</strong></td>
                    </tr>' : '') . '
                    <tr>
                        <td class="info-label" style="padding: 8px 0;">Status:</td>
                        <td style="padding: 8px 0; text-align: right;"><span class="status-badge ' . $statusClass . '">' . $statusLabel . '</span></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0;">Sent by:</td>
                        <td style="padding: 8px 0; text-align: right;">' . $senderName . '</td>
                    </tr>
                </table>
            </div>
            
            <p><strong>Note:</strong> The invoice document is attached to this email as a PDF file.</p>
            
            <p>If you have any questions or need assistance, please contact us at <a href="mailto:' . self::COMPANY_EMAIL . '">' . self::COMPANY_EMAIL . '</a> or call ' . self::COMPANY_PHONE . '.</p>
        ';
        
        return self::wrapTemplate('Invoice from Feza Logistics', '🧾', $bodyContent);
    }
    
    /**
     * Generate Receipt email template
     * 
     * @param array $data Receipt data
     * @return string HTML email content
     */
    public static function receiptEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'Valued Customer');
        $docId = htmlspecialchars($data['doc_id'] ?? '');
        $senderName = htmlspecialchars($data['sender_name'] ?? 'Feza Logistics');
        $message = htmlspecialchars($data['message'] ?? '');
        $amount = htmlspecialchars($data['amount'] ?? '');
        $currency = htmlspecialchars($data['currency'] ?? 'RWF');
        $paymentMethod = htmlspecialchars($data['payment_method'] ?? '');
        
        $bodyContent = '
            <p>Dear <strong>' . $recipientName . '</strong>,</p>
            
            <div class="message-box">' . nl2br($message) . '</div>
            
            <div class="document-info" style="background: #d1fae5;">
                <h3 style="color: #065f46;">✅ Payment Receipt</h3>
                <table style="width: 100%;">
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #a7f3d0;">Receipt Number:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #a7f3d0; text-align: right;"><strong>#' . $docId . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #a7f3d0;">Payment Date:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #a7f3d0; text-align: right;">' . date('F j, Y') . '</td>
                    </tr>
                    ' . ($amount ? '<tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #a7f3d0;">Amount Paid:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #a7f3d0; text-align: right;"><strong style="color: #065f46;">' . $currency . ' ' . $amount . '</strong></td>
                    </tr>' : '') . '
                    ' . ($paymentMethod ? '<tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #a7f3d0;">Payment Method:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #a7f3d0; text-align: right;">' . $paymentMethod . '</td>
                    </tr>' : '') . '
                    <tr>
                        <td class="info-label" style="padding: 8px 0;">Status:</td>
                        <td style="padding: 8px 0; text-align: right;"><span class="status-badge status-paid">Paid</span></td>
                    </tr>
                </table>
            </div>
            
            <p><strong>Note:</strong> Your payment receipt is attached to this email as a PDF file. Please keep this for your records.</p>
            
            <p>Thank you for your payment! If you have any questions, please contact us at <a href="mailto:' . self::COMPANY_EMAIL . '">' . self::COMPANY_EMAIL . '</a> or call ' . self::COMPANY_PHONE . '.</p>
        ';
        
        return self::wrapTemplate('Payment Receipt from Feza Logistics', '🧾', $bodyContent);
    }
    
    /**
     * Generate Quotation email template
     * 
     * @param array $data Quotation data
     * @return string HTML email content
     */
    public static function quotationEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'Valued Customer');
        $docId = htmlspecialchars($data['doc_id'] ?? '');
        $senderName = htmlspecialchars($data['sender_name'] ?? 'Feza Logistics');
        $message = htmlspecialchars($data['message'] ?? '');
        $amount = htmlspecialchars($data['amount'] ?? '');
        $currency = htmlspecialchars($data['currency'] ?? 'RWF');
        $validUntil = htmlspecialchars($data['valid_until'] ?? '');
        
        $bodyContent = '
            <p>Dear <strong>' . $recipientName . '</strong>,</p>
            
            <div class="message-box">' . nl2br($message) . '</div>
            
            <div class="document-info" style="background: #fef3c7;">
                <h3 style="color: #92400e;">📝 Quotation Details</h3>
                <table style="width: 100%;">
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #fcd34d;">Quotation Number:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #fcd34d; text-align: right;"><strong>#' . $docId . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #fcd34d;">Date:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #fcd34d; text-align: right;">' . date('F j, Y') . '</td>
                    </tr>
                    ' . ($validUntil ? '<tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #fcd34d;">Valid Until:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #fcd34d; text-align: right;">' . $validUntil . '</td>
                    </tr>' : '') . '
                    ' . ($amount ? '<tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #fcd34d;">Estimated Total:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #fcd34d; text-align: right;"><strong>' . $currency . ' ' . $amount . '</strong></td>
                    </tr>' : '') . '
                    <tr>
                        <td class="info-label" style="padding: 8px 0;">Prepared by:</td>
                        <td style="padding: 8px 0; text-align: right;">' . $senderName . '</td>
                    </tr>
                </table>
            </div>
            
            <p><strong>Note:</strong> The quotation document is attached to this email as a PDF file. This quote is subject to the terms and conditions stated in the document.</p>
            
            <p>If you would like to proceed or have any questions, please contact us at <a href="mailto:' . self::COMPANY_EMAIL . '">' . self::COMPANY_EMAIL . '</a> or call ' . self::COMPANY_PHONE . '.</p>
        ';
        
        return self::wrapTemplate('Quotation from Feza Logistics', '📝', $bodyContent);
    }
    
    /**
     * Generate Petty Cash Report email template
     * 
     * @param array $data Report data
     * @return string HTML email content
     */
    public static function pettyCashReportEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'Recipient');
        $senderName = htmlspecialchars($data['sender_name'] ?? 'Feza Logistics');
        $message = htmlspecialchars($data['message'] ?? '');
        $dateFrom = htmlspecialchars($data['date_from'] ?? '');
        $dateTo = htmlspecialchars($data['date_to'] ?? '');
        $totalAdded = htmlspecialchars($data['total_added'] ?? '0.00');
        $totalSpent = htmlspecialchars($data['total_spent'] ?? '0.00');
        $balance = htmlspecialchars($data['balance'] ?? '0.00');
        $currency = htmlspecialchars($data['currency'] ?? 'RWF');
        
        $dateRange = $dateFrom && $dateTo ? "$dateFrom to $dateTo" : 'All time';
        
        $bodyContent = '
            <p>Dear <strong>' . $recipientName . '</strong>,</p>
            
            <div class="message-box">' . nl2br($message) . '</div>
            
            <div class="document-info">
                <h3>💰 Petty Cash Report</h3>
                <table style="width: 100%;">
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Report Period:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . $dateRange . '</td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Generated On:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . date('F j, Y, g:i A') . '</td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Total Money Added:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right; color: #065f46;"><strong>+' . $currency . ' ' . $totalAdded . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Total Money Spent:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right; color: #991b1b;"><strong>-' . $currency . ' ' . $totalSpent . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0;">Current Balance:</td>
                        <td style="padding: 8px 0; text-align: right;"><strong style="font-size: 18px;">' . $currency . ' ' . $balance . '</strong></td>
                    </tr>
                </table>
            </div>
            
            <p><strong>Note:</strong> The detailed petty cash report is attached to this email as a PDF file.</p>
            
            <p>If you have any questions about this report, please contact us at <a href="mailto:' . self::COMPANY_EMAIL . '">' . self::COMPANY_EMAIL . '</a> or call ' . self::COMPANY_PHONE . '.</p>
        ';
        
        return self::wrapTemplate('Petty Cash Report - Feza Logistics', '💰', $bodyContent);
    }
    
    /**
     * Generate Transaction Report email template
     * 
     * @param array $data Report data
     * @return string HTML email content
     */
    public static function transactionReportEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'Recipient');
        $senderName = htmlspecialchars($data['sender_name'] ?? 'Feza Logistics');
        $message = htmlspecialchars($data['message'] ?? '');
        $dateFrom = htmlspecialchars($data['date_from'] ?? '');
        $dateTo = htmlspecialchars($data['date_to'] ?? '');
        $totalPayments = htmlspecialchars($data['total_payments'] ?? '0.00');
        $totalExpenses = htmlspecialchars($data['total_expenses'] ?? '0.00');
        $netAmount = htmlspecialchars($data['net_amount'] ?? '0.00');
        $currency = htmlspecialchars($data['currency'] ?? 'RWF');
        $transactionCount = htmlspecialchars($data['transaction_count'] ?? '0');
        
        $dateRange = $dateFrom && $dateTo ? "$dateFrom to $dateTo" : 'All time';
        
        $bodyContent = '
            <p>Dear <strong>' . $recipientName . '</strong>,</p>
            
            <div class="message-box">' . nl2br($message) . '</div>
            
            <div class="document-info">
                <h3>📊 Transaction Report Summary</h3>
                <table style="width: 100%;">
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Report Period:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . $dateRange . '</td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Generated On:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . date('F j, Y, g:i A') . '</td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Total Transactions:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;"><strong>' . $transactionCount . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Total Payments:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right; color: #065f46;"><strong>+' . $currency . ' ' . $totalPayments . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Total Expenses:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right; color: #991b1b;"><strong>-' . $currency . ' ' . $totalExpenses . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0;">Net Amount:</td>
                        <td style="padding: 8px 0; text-align: right;"><strong style="font-size: 18px;">' . $currency . ' ' . $netAmount . '</strong></td>
                    </tr>
                </table>
            </div>
            
            <p><strong>Note:</strong> The detailed transaction report is attached to this email as a PDF file.</p>
            
            <p>If you have any questions about this report, please contact us at <a href="mailto:' . self::COMPANY_EMAIL . '">' . self::COMPANY_EMAIL . '</a> or call ' . self::COMPANY_PHONE . '.</p>
        ';
        
        return self::wrapTemplate('Transaction Report - Feza Logistics', '📊', $bodyContent);
    }
    
    /**
     * Generate General Document email template
     * 
     * @param array $data Document data
     * @return string HTML email content
     */
    public static function generalDocumentEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'Valued Customer');
        $docType = htmlspecialchars($data['doc_type'] ?? 'Document');
        $docId = htmlspecialchars($data['doc_id'] ?? '');
        $senderName = htmlspecialchars($data['sender_name'] ?? 'Feza Logistics');
        $message = htmlspecialchars($data['message'] ?? '');
        
        $docTypeLabel = ucfirst($docType);
        
        $bodyContent = '
            <p>Dear <strong>' . $recipientName . '</strong>,</p>
            
            <div class="message-box">' . nl2br($message) . '</div>
            
            <div class="document-info">
                <h3>📄 Document Details</h3>
                <table style="width: 100%;">
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Document Type:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . $docTypeLabel . '</td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Document ID:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;"><strong>#' . $docId . '</strong></td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0; border-bottom: 1px solid #e5e7eb;">Date:</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; text-align: right;">' . date('F j, Y') . '</td>
                    </tr>
                    <tr>
                        <td class="info-label" style="padding: 8px 0;">Sent by:</td>
                        <td style="padding: 8px 0; text-align: right;">' . $senderName . '</td>
                    </tr>
                </table>
            </div>
            
            <p><strong>Note:</strong> The document is attached to this email as a PDF file.</p>
            
            <p>If you have any questions or need assistance, please contact us at <a href="mailto:' . self::COMPANY_EMAIL . '">' . self::COMPANY_EMAIL . '</a> or call ' . self::COMPANY_PHONE . '.</p>
        ';
        
        return self::wrapTemplate($docTypeLabel . ' from Feza Logistics', '📄', $bodyContent);
    }
    
    /**
     * Get email template by document type
     * 
     * Supported document types:
     * - 'invoice' - Invoice document
     * - 'receipt' - Payment receipt
     * - 'quotation' - Quotation/Quote document
     * - 'petty_cash_report' or 'pettycash' - Petty cash report (aliases for backward compatibility)
     * - 'transaction_report' or 'transaction' - Transaction report (aliases for backward compatibility)
     * - Any other type will use the general document template
     * 
     * @param string $docType Document type identifier
     * @param array $data Template data including recipient_name, doc_id, sender_name, message, etc.
     * @return string HTML email content
     */
    public static function getTemplate(string $docType, array $data): string {
        // Normalize document type to lowercase
        $normalizedType = strtolower(str_replace('-', '_', $docType));
        
        switch ($normalizedType) {
            case 'invoice':
                return self::invoiceEmail($data);
            case 'receipt':
                return self::receiptEmail($data);
            case 'quotation':
            case 'quote':
                return self::quotationEmail($data);
            case 'petty_cash_report':
            case 'pettycash':
            case 'petty_cash':
                return self::pettyCashReportEmail($data);
            case 'transaction_report':
            case 'transaction':
            case 'transactions':
                return self::transactionReportEmail($data);
            case 'otp':
            case 'verification_code':
                return self::otpEmail($data);
            case 'security_alert':
            case 'security':
                return self::securityAlertEmail($data);
            case 'email_verification':
            case 'verify_email':
                return self::emailVerificationEmail($data);
            default:
                return self::generalDocumentEmail($data);
        }
    }
    
    /**
     * Generate OTP/Verification Code email template
     * 
     * @param array $data OTP email data containing:
     *   - recipient_name: Name of the recipient
     *   - otp_code: The OTP code to display
     *   - expiry_minutes: How many minutes until code expires (default: 10)
     *   - purpose: What the OTP is for (login, registration, email_change)
     *   - device_info: Optional device information
     *   - location_info: Optional location information
     * @return string HTML email content
     */
    public static function otpEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'User');
        $otpCode = htmlspecialchars($data['otp_code'] ?? '000000');
        $expiryMinutes = intval($data['expiry_minutes'] ?? 10);
        $purpose = $data['purpose'] ?? 'login';
        $deviceInfo = htmlspecialchars($data['device_info'] ?? '');
        $locationInfo = htmlspecialchars($data['location_info'] ?? '');
        
        // Determine purpose text
        $purposeText = match($purpose) {
            'login' => 'log into your account',
            'registration' => 'verify your new account',
            'email_change' => 'confirm your email address change',
            'password_reset' => 'reset your password',
            default => 'verify your identity'
        };
        
        $deviceSection = '';
        if ($deviceInfo || $locationInfo) {
            $deviceSection = '
                <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin: 20px 0; font-size: 13px;">
                    <p style="margin: 0 0 8px 0; font-weight: 600; color: #64748b;">Request Details:</p>
                    ' . ($deviceInfo ? '<p style="margin: 4px 0; color: #64748b;">📱 Device: ' . $deviceInfo . '</p>' : '') . '
                    ' . ($locationInfo ? '<p style="margin: 4px 0; color: #64748b;">📍 Location: ' . $locationInfo . '</p>' : '') . '
                    <p style="margin: 4px 0; color: #64748b;">🕐 Time: ' . date('F j, Y, g:i a') . '</p>
                </div>
            ';
        }
        
        $bodyContent = '
            <p>Hello <strong>' . $recipientName . '</strong>,</p>
            
            <p>You requested a verification code to ' . $purposeText . '. Please use the code below:</p>
            
            <div style="text-align: center; margin: 30px 0;">
                <div style="display: inline-block; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px dashed #3b82f6; border-radius: 12px; padding: 24px 48px;">
                    <p style="margin: 0 0 8px 0; font-size: 14px; color: #64748b; font-weight: 500;">Your Verification Code</p>
                    <p style="margin: 0; font-size: 36px; font-weight: 700; font-family: \'JetBrains Mono\', \'Fira Code\', monospace; color: #1e40af; letter-spacing: 8px;">' . $otpCode . '</p>
                </div>
            </div>
            
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="margin: 0; color: #92400e; font-size: 14px;">
                    ⏱️ <strong>This code will expire in ' . $expiryMinutes . ' minutes.</strong>
                </p>
            </div>
            
            ' . $deviceSection . '
            
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="margin: 0 0 8px 0; color: #991b1b; font-size: 14px; font-weight: 600;">🔒 Security Notice</p>
                <p style="margin: 0; color: #991b1b; font-size: 13px;">
                    If you didn\'t request this code, please ignore this email. Someone may have entered your email address by mistake.
                    Never share this code with anyone, including Feza Logistics staff.
                </p>
            </div>
            
            <p style="color: #64748b; font-size: 14px;">For your security, this code can only be used once.</p>
        ';
        
        return self::wrapTemplate('Verification Code - Feza Logistics', '🔐', $bodyContent);
    }
    
    /**
     * Generate Security Alert email template
     * 
     * @param array $data Security alert data containing:
     *   - recipient_name: Name of the recipient
     *   - alert_type: Type of alert (new_login, password_change, account_lock, suspicious_activity)
     *   - ip_address: IP address of the activity
     *   - device_info: Device information
     *   - location_info: Location information
     *   - timestamp: When the activity occurred
     * @return string HTML email content
     */
    public static function securityAlertEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'User');
        $alertType = $data['alert_type'] ?? 'new_login';
        $ipAddress = htmlspecialchars($data['ip_address'] ?? 'Unknown');
        $deviceInfo = htmlspecialchars($data['device_info'] ?? 'Unknown Device');
        $locationInfo = htmlspecialchars($data['location_info'] ?? 'Unknown Location');
        $timestamp = $data['timestamp'] ?? date('F j, Y, g:i a');
        
        // Configure alert content based on type
        $alertConfig = match($alertType) {
            'new_login' => [
                'icon' => '🔑',
                'title' => 'New Login to Your Account',
                'description' => 'We detected a new login to your Feza Logistics account.',
                'color' => '#3b82f6',
                'bg_color' => '#eff6ff'
            ],
            'password_change' => [
                'icon' => '🔒',
                'title' => 'Password Changed Successfully',
                'description' => 'Your account password was recently changed.',
                'color' => '#10b981',
                'bg_color' => '#ecfdf5'
            ],
            'account_lock' => [
                'icon' => '🚫',
                'title' => 'Account Temporarily Locked',
                'description' => 'Your account has been temporarily locked due to multiple failed login attempts.',
                'color' => '#ef4444',
                'bg_color' => '#fef2f2'
            ],
            'suspicious_activity' => [
                'icon' => '⚠️',
                'title' => 'Suspicious Activity Detected',
                'description' => 'We detected unusual activity on your account.',
                'color' => '#f59e0b',
                'bg_color' => '#fffbeb'
            ],
            'email_change_request' => [
                'icon' => '📧',
                'title' => 'Email Change Requested',
                'description' => 'A request was made to change the email address associated with your account.',
                'color' => '#06b6d4',
                'bg_color' => '#ecfeff'
            ],
            default => [
                'icon' => '🔔',
                'title' => 'Security Alert',
                'description' => 'An important security event occurred on your account.',
                'color' => '#6b7280',
                'bg_color' => '#f9fafb'
            ]
        };
        
        $bodyContent = '
            <div style="background: ' . $alertConfig['bg_color'] . '; border-left: 4px solid ' . $alertConfig['color'] . '; padding: 16px; margin-bottom: 24px; border-radius: 0 8px 8px 0;">
                <p style="margin: 0; font-size: 18px; font-weight: 600; color: ' . $alertConfig['color'] . ';">
                    ' . $alertConfig['icon'] . ' ' . $alertConfig['title'] . '
                </p>
            </div>
            
            <p>Hello <strong>' . $recipientName . '</strong>,</p>
            
            <p>' . $alertConfig['description'] . '</p>
            
            <div style="background: #f8fafc; border-radius: 8px; padding: 20px; margin: 24px 0;">
                <h3 style="margin: 0 0 16px 0; color: #1e40af; font-size: 16px;">Activity Details</h3>
                <table style="width: 100%; font-size: 14px;">
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-weight: 500; width: 120px;">🕐 Time:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . $timestamp . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-weight: 500;">📱 Device:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . $deviceInfo . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-weight: 500;">📍 Location:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . $locationInfo . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; color: #64748b; font-weight: 500;">🌐 IP Address:</td>
                        <td style="padding: 8px 0; color: #1f2937;">' . $ipAddress . '</td>
                    </tr>
                </table>
            </div>
            
            <p><strong>If this was you:</strong> No action is needed. This is just to keep you informed about activity on your account.</p>
            
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="margin: 0 0 8px 0; color: #991b1b; font-size: 14px; font-weight: 600;">If this wasn\'t you:</p>
                <ul style="margin: 0; padding-left: 20px; color: #991b1b; font-size: 13px;">
                    <li>Change your password immediately</li>
                    <li>Review your account activity</li>
                    <li>Contact our support team at <a href="mailto:' . self::COMPANY_EMAIL . '" style="color: #1e40af;">' . self::COMPANY_EMAIL . '</a></li>
                </ul>
            </div>
            
            <p style="color: #64748b; font-size: 14px;">Stay safe online!</p>
        ';
        
        return self::wrapTemplate('Security Alert - Feza Logistics', '🔐', $bodyContent);
    }
    
    /**
     * Generate Email Verification email template
     * 
     * @param array $data Verification email data containing:
     *   - recipient_name: Name of the recipient
     *   - otp_code: Verification code (if OTP-based)
     *   - verification_link: Verification link (if link-based)
     *   - expiry_minutes: How long the verification is valid
     *   - purpose: registration, email_change, etc.
     * @return string HTML email content
     */
    public static function emailVerificationEmail(array $data): string {
        $recipientName = htmlspecialchars($data['recipient_name'] ?? 'User');
        $otpCode = htmlspecialchars($data['otp_code'] ?? '');
        $verificationLink = $data['verification_link'] ?? '';
        $expiryMinutes = intval($data['expiry_minutes'] ?? 15);
        $purpose = $data['purpose'] ?? 'registration';
        
        // Determine purpose-specific content
        $purposeConfig = match($purpose) {
            'registration' => [
                'title' => 'Welcome to Feza Logistics!',
                'intro' => 'Thank you for creating an account with Feza Logistics. We\'re excited to have you on board!',
                'instruction' => 'To complete your registration, please verify your email address using the code below:'
            ],
            'email_change' => [
                'title' => 'Verify Your New Email Address',
                'intro' => 'You requested to change the email address associated with your Feza Logistics account.',
                'instruction' => 'To confirm this change, please enter the verification code below:'
            ],
            default => [
                'title' => 'Email Verification Required',
                'intro' => 'Please verify your email address to continue using your Feza Logistics account.',
                'instruction' => 'Enter the verification code below to verify your email:'
            ]
        };
        
        $verificationSection = '';
        if ($otpCode) {
            $verificationSection = '
                <div style="text-align: center; margin: 30px 0;">
                    <div style="display: inline-block; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px dashed #10b981; border-radius: 12px; padding: 24px 48px;">
                        <p style="margin: 0 0 8px 0; font-size: 14px; color: #64748b; font-weight: 500;">Your Verification Code</p>
                        <p style="margin: 0; font-size: 36px; font-weight: 700; font-family: \'JetBrains Mono\', \'Fira Code\', monospace; color: #047857; letter-spacing: 8px;">' . $otpCode . '</p>
                    </div>
                </div>
            ';
        }
        
        if ($verificationLink) {
            $verificationSection .= '
                <div style="text-align: center; margin: 30px 0;">
                    <a href="' . htmlspecialchars($verificationLink) . '" style="display: inline-block; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                        ✓ Verify Email Address
                    </a>
                    <p style="margin-top: 12px; font-size: 13px; color: #64748b;">
                        Or copy and paste this link into your browser:<br>
                        <span style="color: #3b82f6; word-break: break-all;">' . htmlspecialchars($verificationLink) . '</span>
                    </p>
                </div>
            ';
        }
        
        $bodyContent = '
            <p>Hello <strong>' . $recipientName . '</strong>,</p>
            
            <p>' . $purposeConfig['intro'] . '</p>
            
            <p>' . $purposeConfig['instruction'] . '</p>
            
            ' . $verificationSection . '
            
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; border-radius: 0 8px 8px 0;">
                <p style="margin: 0; color: #92400e; font-size: 14px;">
                    ⏱️ <strong>This verification expires in ' . $expiryMinutes . ' minutes.</strong>
                </p>
            </div>
            
            <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin: 20px 0;">
                <p style="margin: 0 0 12px 0; color: #1f2937; font-weight: 600;">What\'s next?</p>
                <ul style="margin: 0; padding-left: 20px; color: #64748b; font-size: 14px;">
                    <li>Once verified, you\'ll have full access to your account</li>
                    <li>Manage your financial documents and transactions</li>
                    <li>Access our AI-powered financial assistant</li>
                    <li>Generate professional invoices, receipts, and quotations</li>
                </ul>
            </div>
            
            <p style="color: #64748b; font-size: 14px;">If you didn\'t create this account, you can safely ignore this email.</p>
        ';
        
        return self::wrapTemplate($purposeConfig['title'] . ' - Feza Logistics', '✉️', $bodyContent);
    }
}
