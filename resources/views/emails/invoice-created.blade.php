<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Created</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-details { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; }
        .detail-label { font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; }
        .amount { font-size: 24px; font-weight: bold; color: #10b981; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Created</h1>
            <p>{{ $companyName }}</p>
        </div>

        <p>Hello {{ $client->name }},</p>

        <p>We've created a new invoice for you. Here are the details:</p>

        <div class="invoice-details">
            <div class="detail-row">
                <span class="detail-label">Invoice Number:</span>
                <span>{{ $invoice->invoice_number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Issue Date:</span>
                <span>{{ $invoice->issue_date->format('M d, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Due Date:</span>
                <span>{{ $invoice->due_date->format('M d, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span>{{ ucfirst($invoice->status) }}</span>
            </div>
            @if($invoice->notes)
            <div class="detail-row">
                <span class="detail-label">Notes:</span>
                <span>{{ $invoice->notes }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="amount">{{ $invoice->getFormattedTotal() }}</span>
            </div>
        </div>

        <p>Please review the invoice and let us know if you have any questions.</p>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>{{ $companyName }}</p>
        </div>
    </div>
</body>
</html>
