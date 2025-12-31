<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 30px; }
        .payment-details { background: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; }
        .detail-label { font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; font-size: 14px; color: #6b7280; }
        .amount { font-size: 24px; font-weight: bold; color: #10b981; }
        .payment-method { background: #3b82f6; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Payment Receipt</h1>
            <p>{{ $companyName }}</p>
        </div>

        <p>Hello {{ $client->name }},</p>

        <p>Thank you for your payment! Here is your receipt:</p>

        <div class="payment-details">
            <div class="detail-row">
                <span class="detail-label">Invoice Number:</span>
                <span>{{ $invoice->invoice_number }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Amount:</span>
                <span class="amount">{{ $payment->getFormattedAmount() }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="payment-method">{{ $payment->getPaymentMethodLabel() }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Date:</span>
                <span>{{ $payment->payment_date->format('M d, Y') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Invoice Total:</span>
                <span>{{ $invoice->getFormattedTotal() }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Remaining Balance:</span>
                <span>{{ $invoice->getFormattedRemainingBalance() }}</span>
            </div>
            @if($payment->notes)
            <div class="detail-row">
                <span class="detail-label">Notes:</span>
                <span>{{ $payment->notes }}</span>
            </div>
            @endif
        </div>

        <p>Your payment has been successfully processed and applied to the invoice.</p>

        @if($remainingBalance > 0)
        <p>There is still a remaining balance of {{ $invoice->getFormattedRemainingBalance() }} on this invoice.</p>
        @else
        <p>This invoice is now fully paid. Thank you!</p>
        @endif

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>{{ $companyName }}</p>
        </div>
    </div>
</body>
</html>
