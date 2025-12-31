<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Paid</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-details { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; }
        .detail-label { font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; }
        .amount { font-size: 24px; font-weight: bold; color: #10b981; }
        .paid-badge { background: #10b981; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Paid</h1>
            <p>{{ $companyName }}</p>
        </div>

        <p>Hello {{ $client->name }},</p>

        <p>Great news! Your invoice has been marked as paid.</p>

        <div class="invoice-details">
            <div class="detail-row">
                <span class="detail-label">Invoice Number:</span>
                <span>{{ $invoice->invoice_number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="paid-badge">PAID</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="amount">{{ $invoice->getFormattedTotal() }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Paid:</span>
                <span>{{ $invoice->getFormattedTotalPaid() }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Paid On:</span>
                <span>{{ $paidAt->format('M d, Y \a\t h:i A') }}</span>
            </div>
        </div>

        <p>Thank you for your prompt payment. We appreciate your business!</p>

        <div class="footer">
            <p>Best regards,</p>
            <p>{{ $companyName }}</p>
        </div>
    </div>
</body>
</html>
