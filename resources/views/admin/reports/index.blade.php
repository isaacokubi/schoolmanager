@extends('layouts.app')

@section('body')
<div class="container" style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:20px">
        <div><h1 style="margin:0">School Reports</h1><p style="margin:6px 0;color:#666">Operational summaries generated {{ $generatedAt->format('d M Y H:i') }}.</p></div>
        <button onclick="window.print()" style="padding:10px 16px;border:0;border-radius:6px;cursor:pointer">Print report</button>
    </div>
    <nav style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
        @foreach(['overview'=>'Overview','students'=>'Students','fees'=>'Fees','attendance'=>'Attendance','results'=>'Results','admissions'=>'Admissions'] as $key=>$label)
            <a href="{{ route('admin.reports', ['report'=>$key]) }}" style="padding:8px 12px;border:1px solid #ddd;border-radius:6px;text-decoration:none;{{ $report === $key ? 'font-weight:bold' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px">
        @foreach([['Students',$studentCount],['Parents',$parentCount],['Teachers',$teacherCount],['Classes',$classCount]] as $card)
            <div style="border:1px solid #ddd;border-radius:8px;padding:16px"><div style="color:#666">{{ $card[0] }}</div><strong style="font-size:24px">{{ $card[1] }}</strong></div>
        @endforeach
        @if(isset($paymentTotal))<div style="border:1px solid #ddd;border-radius:8px;padding:16px"><div style="color:#666">Payments received</div><strong style="font-size:24px">KES {{ number_format($paymentTotal,2) }}</strong></div><div style="border:1px solid #ddd;border-radius:8px;padding:16px"><div style="color:#666">Outstanding fees</div><strong style="font-size:24px">KES {{ number_format($outstanding,2) }}</strong></div>@endif
        @if(isset($averageMarks))<div style="border:1px solid #ddd;border-radius:8px;padding:16px"><div style="color:#666">Average marks</div><strong style="font-size:24px">{{ number_format($averageMarks,2) }}</strong></div>@endif
    </div>

    @if(isset($students))
    <section><h2>Students</h2><div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Admission</th><th>Name</th><th>Class</th><th>Parent</th><th>Balance</th></tr></thead><tbody>@forelse($students as $student)<tr><td>{{ $student->admission_number }}</td><td>{{ $student->name }}</td><td>{{ $student->class_label ? $student->class_label . ($student->stream ? ' - '.$student->stream : '') : ($student->class_name ?: '—') }}</td><td>{{ $student->parent_label ?: '—' }}</td><td>KES {{ number_format((float)($student->fee_balance ?? 0),2) }}</td></tr>@empty<tr><td colspan="5">No students found.</td></tr>@endforelse</tbody></table></div></section>
    @endif

    @if(isset($payments))
    <section><h2>Fees & Payments</h2><div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Date</th><th>Student</th><th>Amount</th><th>Status</th><th>M-Pesa receipt</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ $payment->created_at }}</td><td>{{ $payment->student_name ?: $payment->student_id }}</td><td>KES {{ number_format((float)($payment->{$paymentAmountColumn} ?? 0),2) }}</td><td>{{ $payment->status ?? '—' }}</td><td>{{ $payment->mpesa_receipt ?? '—' }}</td></tr>@empty<tr><td colspan="5">No payments found.</td></tr>@endforelse</tbody></table></div></section>
    @endif

    @if(isset($attendance))
    <section><h2>Attendance</h2><p>Present: {{ $attendanceSummary['present'] ?? 0 }} · Absent: {{ $attendanceSummary['absent'] ?? 0 }} · Late: {{ $attendanceSummary['late'] ?? 0 }} · Excused: {{ $attendanceSummary['excused'] ?? 0 }}</p><div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Date</th><th>Student</th><th>Status</th></tr></thead><tbody>@forelse($attendance as $row)<tr><td>{{ $row->attendance_date }}</td><td>{{ $row->student_name }}</td><td>{{ ucfirst($row->status) }}</td></tr>@empty<tr><td colspan="3">No attendance records found.</td></tr>@endforelse</tbody></table></div></section>
    @endif

    @if(isset($results))
    <section><h2>Academic Results</h2><div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Exam</th><th>Student</th><th>Subject</th><th>Marks</th><th>Grade</th></tr></thead><tbody>@forelse($results as $row)<tr><td>{{ $row->exam_name }}</td><td>{{ $row->student_name }}</td><td>{{ $row->subject_name }}</td><td>{{ number_format((float)$row->marks,2) }}</td><td>{{ $row->grade }}</td></tr>@empty<tr><td colspan="5">No results found.</td></tr>@endforelse</tbody></table></div></section>
    @endif

    @if(isset($applications))
    <section><h2>Admissions</h2><p>@foreach($applicationSummary as $status=>$total) <strong>{{ ucfirst($status) }}:</strong> {{ $total }} &nbsp; @endforeach</p><div style="overflow:auto"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Date</th><th>Student</th><th>Requested class</th><th>Status</th></tr></thead><tbody>@forelse($applications as $application)<tr><td>{{ $application->created_at }}</td><td>{{ $application->student_name }}</td><td>{{ $application->requested_class }}</td><td>{{ ucfirst($application->status) }}</td></tr>@empty<tr><td colspan="4">No applications found.</td></tr>@endforelse</tbody></table></div></section>
    @endif
</div>
<style>th,td{padding:9px;border-bottom:1px solid #eee;text-align:left}section{margin-bottom:28px}@media print{nav,button{display:none!important}body{font-size:11px}}</style>
@endsection
