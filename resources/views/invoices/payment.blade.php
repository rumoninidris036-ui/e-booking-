<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $invoiceNumber }}</title>
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                color: #111827;
                font-family: DejaVu Sans, sans-serif;
                font-size: 13px;
                line-height: 1.55;
                margin: 0;
            }

            .page {
                padding: 42px;
            }

            .header {
                border-bottom: 2px solid #111827;
                padding-bottom: 22px;
            }

            .brand {
                font-size: 26px;
                font-weight: 700;
                letter-spacing: 1px;
                text-transform: uppercase;
            }

            .muted {
                color: #6b7280;
            }

            .invoice-title {
                float: right;
                text-align: right;
            }

            .invoice-title h1 {
                font-size: 30px;
                margin: 0;
                text-transform: uppercase;
            }

            .grid {
                display: table;
                margin-top: 30px;
                width: 100%;
            }

            .col {
                display: table-cell;
                vertical-align: top;
                width: 50%;
            }

            .label {
                color: #6b7280;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 1.5px;
                margin-bottom: 5px;
                text-transform: uppercase;
            }

            .box {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 18px;
            }

            table {
                border-collapse: collapse;
                margin-top: 30px;
                width: 100%;
            }

            th {
                background: #111827;
                color: #ffffff;
                font-size: 11px;
                letter-spacing: 1px;
                padding: 12px;
                text-align: left;
                text-transform: uppercase;
            }

            td {
                border-bottom: 1px solid #e5e7eb;
                padding: 14px 12px;
                vertical-align: top;
            }

            .right {
                text-align: right;
            }

            .total {
                font-size: 20px;
                font-weight: 700;
            }

            .status {
                background: #dcfce7;
                border-radius: 999px;
                color: #166534;
                display: inline-block;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 1px;
                padding: 6px 12px;
                text-transform: uppercase;
            }

            .footer {
                border-top: 1px solid #e5e7eb;
                bottom: 34px;
                color: #6b7280;
                font-size: 11px;
                left: 42px;
                padding-top: 12px;
                position: fixed;
                right: 42px;
            }
        </style>
    </head>
    <body>
        <div class="page">
            <div class="header">
                <div class="invoice-title">
                    <h1>Invoice</h1>
                    <div class="muted">{{ $invoiceNumber }}</div>
                </div>

                <div class="brand">SmashCourt</div>
                <div class="muted">Online badminton court booking</div>
            </div>

            <div class="grid">
                <div class="col">
                    <div class="label">Billed To</div>
                    <div class="box">
                        <strong>{{ $customerName }}</strong><br>
                        {{ $customerContact ?? '-' }}<br>
                        {{ $customerEmail ?? '-' }}<br>
                        Kode Booking: {{ $booking->booking_code }}
                    </div>
                </div>
                <div class="col">
                    <div class="label">Pembayaran</div>
                    <div class="box">
                        Order ID: {{ $payment->order_id }}<br>
                        Paid At: {{ optional($payment->paid_at)->format('d M Y H:i') ?? '-' }}<br>
                        Method: {{ $payment->midtrans_payment_type ?? 'Midtrans' }}<br>
                        <span class="status">{{ $payment->status }}</span>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $field->name }}</strong><br>
                            <span class="muted">
                                {{ $booking->booking_date->format('d M Y') }},
                                {{ substr((string) $booking->start_time, 0, 5) }} -
                                {{ substr((string) $booking->end_time, 0, 5) }}
                            </span>
                        </td>
                        <td class="right">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="right total">Total</td>
                        <td class="right total">Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="footer">
                This invoice was generated automatically by SmashCourt after a successful Midtrans payment.
            </div>
        </div>
    </body>
</html>
