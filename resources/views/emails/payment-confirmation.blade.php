<!doctype html>
<html><body style="font-family:Arial,sans-serif;background:#f4f7fb;padding:24px;color:#1f2937">
<div style="max-width:620px;margin:auto;background:#fff;border-radius:14px;padding:28px;border:1px solid #e5e7eb">
<h2 style="margin-top:0">School fee payment received</h2>
<p>Dear {{ $recipientName }},</p>
<p>We have received your M-Pesa school fee payment for <strong>{{ $student->name }}</strong>.</p>
<table style="width:100%;border-collapse:collapse;margin:20px 0">
<tr><td style="padding:8px 0">Amount</td><td style="padding:8px 0;text-align:right"><strong>KES {{ number_format((float)$payment->amount, 2) }}</strong></td></tr>
<tr><td style="padding:8px 0">M-Pesa receipt</td><td style="padding:8px 0;text-align:right"><strong>{{ $payment->mpesa_receipt }}</strong></td></tr>
<tr><td style="padding:8px 0">Learner</td><td style="padding:8px 0;text-align:right">{{ $student->name }}</td></tr>
<tr><td style="padding:8px 0">Admission number</td><td style="padding:8px 0;text-align:right">{{ $student->admission_number }}</td></tr>
<tr><td style="padding:8px 0">Remaining fee balance</td><td style="padding:8px 0;text-align:right"><strong>KES {{ number_format((float)$student->fee_balance, 2) }}</strong></td></tr>
</table>
<p>Keep this email as your payment confirmation.</p>
<p>Regards,<br>{{ config('app.name') }}</p>
</div></body></html>
