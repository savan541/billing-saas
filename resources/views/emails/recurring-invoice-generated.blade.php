<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recurring Invoice Generated</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 30px; }
        .invoice-details { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .recurring-details { background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; }
        .detail-label { font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; }
        .amount { font-size: 24px; font-weight: bold; color: #10b981; }
        .recurring-badge { background: #f59e0b; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Recurring Invoice Generated</h1>
            <p>{{ $companyName }}</p>
        </div>

        <p>Hello {{ $client->name }},</p>

        <p>A new invoice has been automatically generated from your recurring invoice setup.</p>

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
                <span class="detail-label">Total Amount:</span>
                <span class="amount">{{ $invoice->getFormattedTotal() }}</span>
            </div>
            @if($invoice->notes)
            <div class="detail-row">
                <span class="detail-label">Notes:</span>
                <span>{{ $invoice->notes }}</span>
            </div>
            @endif
        </div>

        <div class="recurring-details">
            <h3>Recurring Invoice Details</h3>
            <div class="detail-row">
                <span class="detail-label">Template:</span>
                <span>{{ $recurringInvoice->title }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Frequency:</span>
                <span class="recurring-badge">{{ $recurringInvoice->getFrequencyLabel() }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Next Generation:</span>
                <span>{{ $nextRunDate->format('M d, Y') }}</span>
            </div>
        </div>

        <p>Please review the invoice and process payment as usual. The next recurring invoice will be generated automatically on {{ $nextRunDate->format('M d, Y') }}.</p>

        <div class="footer">
            <p>Thank you for your continued business!</p>
            <p>{{ $companyName }}</p>
        </div>
    </div>
</body>
</html>
