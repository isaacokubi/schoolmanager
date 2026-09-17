<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - {{ $receiptNumber }}</title>
    <style>
        @page {
            margin: 32px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 3px solid #166534;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            color: #166534;
        }

        .receipt-title {
            margin-top: 5px;
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }

        .meta {
            margin-top: 8px;
            color: #6b7280;
        }

        .status {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 12px;
        }

        .section {
            margin-top: 22px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #166534;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        td:first-child {
            width: 38%;
            font-weight: bold;
            color: #4b5563;
        }

        .amount {
            font-size: 20px;
            font-weight: bold;
            color: #166534;
        }

        .footer {
            margin-top: 36px;
            padding-top: 14px;
            border-top: 1px solid #d1d5db;
            color: #6b7280;
            font-size: 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="school-name">{{ $schoolName }}</div>
    <div class="receipt-title">OFFICIAL SCHOOL FEES PAYMENT RECEIPT</div>
    <div class="meta">
        Receipt No: <strong>{{ $receiptNumber }}</strong>
    </div>
    <div class="status">PAYMENT VERIFIED</div>
</div>

<div class="section">
    <div class="section-title">LEARNER DETAILS</div>

    <table>
        <tr>
            <td>Learner Name</td>
            <td>{{ $student->name }}</td>
        </tr>

        @if(!empty($student->admission_number))
        <tr>
            <td>Admission Number</td>
            <td>{{ $student->admission_number }}</td>
        </tr>
        @endif

        @if(!empty($student->class_name))
        <tr>
            <td>Class</td>
            <td>{{ $student->class_name }}</td>
        </tr>
        @endif
    </table>
</div>

<div class="section">
    <div class="section-title">PARENT / SPONSOR</div>

    <table>
        <tr>
            <td>Name</td>
            <td>{{ $parent->name ?? $student->parent_name ?? 'Parent/Sponsor' }}</td>
        </tr>

        @if(!empty($parent->relationship))
        <tr>
            <td>Relationship</td>
            <td>{{ $parent->relationship }}</td>
        </tr>
        @endif

        <tr>
            <td>Payment Phone</td>
            <td>{{ $payment->parent_phone ?: 'N/A' }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">PAYMENT DETAILS</div>

    <table>
        <tr>
            <td>Payment Type</td>
            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_type ?: 'School Fees')) }}</td>
        </tr>

        <tr>
            <td>Payment Channel</td>
            <td>{{ strtoupper(str_replace('_', ' ', $payment->channel ?: 'M-Pesa')) }}</td>
        </tr>

        <tr>
            <td>M-Pesa Receipt</td>
            <td><strong>{{ $receiptNumber }}</strong></td>
        </tr>

        <tr>
            <td>Account Reference</td>
            <td>{{ $payment->account_reference ?: 'N/A' }}</td>
        </tr>

        <tr>
            <td>Payment Date</td>
            <td>{{ $payment->paid_at ?: now() }}</td>
        </tr>

        <tr>
            <td>Amount Paid</td>
            <td class="amount">
                KES {{ number_format((float) $payment->amount, 2) }}
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    This is an electronically generated official payment receipt.
    The payment was verified against the M-Pesa transaction details before
    the receipt was issued.
    <br><br>
    Please retain this receipt for your school fees records.
</div>

</body>
</html>
