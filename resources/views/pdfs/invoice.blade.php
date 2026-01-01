<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }
        
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .company-details {
            font-size: 11px;
            line-height: 1.5;
        }
        
        .invoice-details {
            text-align: right;
            flex: 0 0 300px;
        }
        
        .invoice-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .invoice-meta {
            font-size: 11px;
            line-height: 1.6;
        }
        
        .invoice-meta strong {
            display: inline-block;
            width: 80px;
            text-align: right;
            margin-right: 10px;
        }
        
        .billing-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 40px;
        }
        
        .bill-to, .ship-to {
            flex: 1;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .client-info {
            font-size: 11px;
            line-height: 1.5;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #333;
        }
        
        .items-table td {
            border: 1px solid #ddd;
            padding: 10px 8px;
            font-size: 11px;
        }
        
        .items-table .description {
            min-width: 200px;
        }
        
        .items-table .quantity,
        .items-table .unit-price,
        .items-table .total {
            text-align: right;
            width: 100px;
        }
        
        .items-table .quantity {
            width: 80px;
        }
        
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        
        .summary-table {
            width: 300px;
            border-collapse: collapse;
        }
        
        .summary-table td {
            padding: 8px 12px;
            font-size: 11px;
            border: none;
        }
        
        .summary-table .label {
            text-align: left;
            font-weight: normal;
        }
        
        .summary-table .value {
            text-align: right;
            font-weight: bold;
        }
        
        .summary-table .total-row {
            border-top: 2px solid #333;
            font-size: 14px;
            font-weight: bold;
        }
        
        .notes-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .notes-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 12px;
            text-transform: uppercase;
            color: #333;
        }
        
        .notes-content {
            font-size: 11px;
            line-height: 1.5;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .status-draft { background-color: #6c757d; color: white; }
        .status-sent { background-color: #007bff; color: white; }
        .status-paid { background-color: #28a745; color: white; }
        .status-overdue { background-color: #dc3545; color: white; }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-failed { background-color: #dc3545; color: white; }
        .payment-amount { font-weight: bold; color: #28a745; }
        
        .compact-table {
            font-size: 10px;
        }
        .compact-table th,
        .compact-table td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        .compact-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .page-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $company['name'] }}</div>
            <div class="company-details">
                @if($company['address'])
                    <div>{{ $company['address'] }}</div>
                @endif
                @if($company['phone'])
                    <div>Phone: {{ $company['phone'] }}</div>
                @endif
                <div>Email: {{ $company['email'] }}</div>
                @if($company['website'])
                    <div>Website: {{ $company['website'] }}</div>
                @endif
            </div>
        </div>
        <div class="invoice-details">
            <div class="status-badge status-{{ $invoice->status }}">{{ $invoice->status }}</div>
            <div class="invoice-number">Invoice #{{ $invoice->invoice_number }}</div>
            <div class="invoice-meta">
                <div><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}</div>
                <div><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</div>
                @if($invoice->paid_at)
                    <div><strong>Paid Date:</strong> {{ $invoice->paid_at->format('M d, Y') }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="billing-section">
        <div class="bill-to">
            <div class="section-title">Bill To</div>
            <div class="client-info">
                <div><strong>{{ $invoice->client->name }}</strong></div>
                @if($invoice->client->email)
                    <div>{{ $invoice->client->email }}</div>
                @endif
                @if($invoice->client->phone)
                    <div>{{ $invoice->client->phone }}</div>
                @endif
                @if($invoice->client->address)
                    <div>{{ $invoice->client->address }}</div>
                @endif
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="description">Description</th>
                <th class="quantity">Quantity</th>
                <th class="unit-price">Unit Price</th>
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td class="description">{{ $item->description }}</td>
                    <td class="quantity">{{ number_format($item->quantity, 2) }}</td>
                    <td class="unit-price">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="total">${{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax > 0)
                <tr>
                    <td class="label">Tax:</td>
                    <td class="value">${{ number_format($invoice->tax, 2) }}</td>
                </tr>
            @endif
            @if($invoice->discount > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="value">-${{ number_format($invoice->discount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td class="label">Total:</td>
                <td class="value">${{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>
    
    @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="summary-section page-break">
            <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; color: #333;">Payment Summary</h3>
            <table class="compact-table" style="margin-bottom: 15px;">
                <tr>
                    <td class="label">Total Paid:</td>
                    <td class="value"><span class="payment-amount">{{ $invoice->currency_symbol }}{{ number_format($invoice->total_paid, 2) }}</span></td>
                </tr>
                <tr>
                    <td class="label">Remaining Balance:</td>
                    <td class="value"><span class="payment-amount">{{ $invoice->currency_symbol }}{{ number_format($invoice->remaining_balance, 2) }}</span></td>
                </tr>
            </table>
        </div>
    @endif

    @if($invoice->payments && $invoice->payments->count() > 0)
        <div class="summary-section page-break">
            <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; color: #333;">Payment History</h3>
            <table class="compact-table" style="margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 6px 8px; border: 1px solid #ddd; background-color: #f8f9fa;">Date</th>
                        <th style="text-align: left; padding: 6px 8px; border: 1px solid #ddd; background-color: #f8f9fa;">Method</th>
                        <th style="text-align: left; padding: 6px 8px; border: 1px solid #ddd; background-color: #f8f9fa;">Amount</th>
                        <th style="text-align: left; padding: 6px 8px; border: 1px solid #ddd; background-color: #f8f9fa;">Status</th>
                        <th style="text-align: left; padding: 6px 8px; border: 1px solid #ddd; background-color: #f8f9fa;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->payments as $payment)
                        <tr>
                            <td style="padding: 6px 8px; border: 1px solid #ddd;">{{ $payment->created_at->format('M d, Y') }}</td>
                            <td style="padding: 6px 8px; border: 1px solid #ddd;">{{ $payment->getPaymentMethodLabel() }}</td>
                            <td style="padding: 6px 8px; border: 1px solid #ddd;"><span class="payment-amount">{{ $invoice->currency_symbol }}{{ number_format($payment->amount, 2) }}</span></td>
                            <td style="padding: 6px 8px; border: 1px solid #ddd;">
                                <span class="status-badge status-{{ $payment->status ?? 'pending' }}">{{ ucfirst($payment->status ?? 'pending') }}</span>
                            </td>
                            <td style="padding: 6px 8px; border: 1px solid #ddd;">{{ $payment->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($invoice->notes)
        <div class="notes-section">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
        </div>
    @endif

    <div class="footer">
        <div>Thank you for your business!</div>
        <div>This is a computer-generated invoice and does not require a signature.</div>
    </div>
</body>
</html>
