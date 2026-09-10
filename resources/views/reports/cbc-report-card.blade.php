<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CBC Report Card — {{ $student->name }}</title>
<style>
*{box-sizing:border-box}
@page{size:A4 portrait;margin:12mm}
body{margin:0;background:#eef2f7;color:#172338;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.45}
.sheet{width:100%;max-width:900px;margin:24px auto;background:#fff;border:1px solid #d9e1ea;box-shadow:0 12px 40px rgba(28,47,74,.12);padding:28px}
.actions{text-align:center;margin:0 auto 16px;display:flex;justify-content:center;gap:9px;flex-wrap:wrap}
.actions a,.actions button{padding:10px 16px;border:0;background:#163f70;color:#fff;border-radius:6px;cursor:pointer;text-decoration:none;font-size:11px;font-weight:bold}
.actions a{background:#237a55}
.header{border:1px solid #d7e0ea;background:#f8fafc}
.header-top{padding:20px 22px 17px;text-align:center;border-bottom:4px solid #163f70}
.school-mark{width:48px;height:48px;margin:0 auto 9px;border:2px solid #163f70;border-radius:50%;display:table}
.school-mark span{display:table-cell;vertical-align:middle;text-align:center;color:#163f70;font-size:15px;font-weight:bold}
.school-name{margin:0;color:#122d50;font-size:25px;line-height:1.15;text-transform:uppercase;letter-spacing:.7px}
.school-subtitle{margin:5px 0 0;color:#66758a;font-size:10px;text-transform:uppercase;letter-spacing:1.2px}
.document-label{display:inline-block;margin-top:13px;padding:5px 10px;border-radius:20px;background:#e8f0f9;color:#163f70;font-size:9px;font-weight:bold;text-transform:uppercase;letter-spacing:.7px}
.header-meta{width:100%;border-collapse:collapse;background:#fff}
.header-meta td{padding:8px 12px;border-right:1px solid #e0e6ed;text-align:center}
.header-meta td:last-child{border-right:0}
.header-meta small{display:block;color:#77859a;text-transform:uppercase;font-size:7px;font-weight:bold;letter-spacing:.5px}
.header-meta strong{display:block;margin-top:2px;color:#20324a;font-size:10px}
.title{text-align:center;padding:20px 0 14px}
.title h1{margin:0;color:#163f70;font-size:18px;letter-spacing:.6px}
.title p{margin:4px 0 0;color:#66758a;font-size:10px}
.identity{width:100%;border-collapse:separate;border-spacing:7px;margin:0 -7px 12px;width:calc(100% + 14px)}
.identity td{width:25%;padding:10px 11px;border:1px solid #dce4ed;background:#f8fafc;vertical-align:top}
.identity small{display:block;color:#718096;text-transform:uppercase;font-size:7px;font-weight:bold;letter-spacing:.45px}
.identity strong{display:block;margin-top:4px;color:#172b45;font-size:10px}
.status-complete{color:#18714d!important}
.status-incomplete{color:#a85c13!important}
.section-title{margin:17px 0 7px;color:#163f70;font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:.8px;border-left:4px solid #163f70;padding-left:8px}
.table{width:100%;border-collapse:collapse;table-layout:fixed}
.table th{padding:9px 8px;background:#163f70;color:#fff;border:1px solid #163f70;text-align:left;font-size:8.5px;text-transform:uppercase;letter-spacing:.35px}
.table td{padding:8px;border:1px solid #d8e0e9;font-size:9.5px;vertical-align:middle}
.table tbody tr:nth-child(even) td{background:#f8fafc}
.table th:nth-child(1){width:25%}.table th:nth-child(2){width:11%;text-align:center}.table th:nth-child(3){width:19%;text-align:center}.table th:nth-child(4){width:10%;text-align:center}.table th:nth-child(5){width:35%}
.table td:nth-child(2),.table td:nth-child(3),.table td:nth-child(4){text-align:center}
.table td strong{font-size:9.5px;color:#1e3048}.subject-code{color:#7b8798;font-size:7.5px;letter-spacing:.3px}
.level{font-weight:bold;text-align:center;background:#eaf3ff!important;color:#145ca6}
.missed{background:#fff0f0!important;color:#a3212c;font-weight:bold;text-align:center}
.summary{width:100%;border-collapse:separate;border-spacing:7px;margin:13px -7px 0;width:calc(100% + 14px)}
.summary td{width:25%;border:1px solid #d9e2eb;padding:11px 7px;text-align:center;background:#fff}
.summary b{display:block;color:#163f70;font-size:17px;line-height:1.1}
.summary span{display:block;margin-top:4px;color:#718096;font-size:7.5px;text-transform:uppercase;letter-spacing:.35px}
.two-col{width:100%;border-collapse:separate;border-spacing:7px;margin:8px -7px 0;width:calc(100% + 14px)}
.two-col>tbody>tr>td{width:50%;vertical-align:top}
.panel{border:1px solid #d9e2eb;background:#f8fafc;padding:11px}
.panel h3{margin:0 0 7px;color:#163f70;font-size:9px;text-transform:uppercase;letter-spacing:.6px}
.panel p{margin:3px 0;color:#526177;font-size:8.5px}
.scale{width:100%;border-collapse:collapse}
.scale td{border:1px solid #d9e2eb;padding:5px 6px;font-size:8px;background:#fff}
.scale td:first-child{width:18%;font-weight:bold;color:#163f70;text-align:center}
.attendance{width:100%;border-collapse:collapse}
.attendance td{padding:4px 3px;font-size:8px;color:#526177;border-bottom:1px solid #e2e7ed}
.attendance td:last-child{text-align:right;font-weight:bold;color:#20324a}
.note{margin-top:10px;padding:9px 11px;border-left:4px solid #163f70;background:#f4f7fa;color:#56657a;font-size:8px}
.note strong{color:#243852}
.signatures{width:100%;border-collapse:separate;border-spacing:10px;margin:37px -10px 0;width:calc(100% + 20px)}
.signatures td{width:33.333%;padding:28px 8px 0;border-top:1px solid #6f7d8e;text-align:center;color:#34445a;font-size:8.5px;font-weight:bold}
.signatures small{display:block;margin-top:4px;color:#8792a2;font-weight:normal;font-size:7px}
.footer{margin-top:18px;padding-top:8px;border-top:1px solid #dbe2e9;text-align:center;color:#8a95a5;font-size:7px}
@media print{body{background:#fff}.sheet{max-width:none;margin:0;border:0;box-shadow:none;padding:0}.actions{display:none}.header{break-inside:avoid}.table{break-inside:auto}.table tr{break-inside:avoid}.summary,.two-col,.signatures{break-inside:avoid}}
@media(max-width:650px){.sheet{padding:16px}.school-name{font-size:20px}.identity,.summary,.two-col,.signatures{display:block;width:100%;margin:0}.identity td,.summary td,.two-col>tbody>tr>td,.signatures td{display:block;width:100%;margin:6px 0}.table{font-size:8px}.table th,.table td{padding:6px 4px}}
</style>
</head>
<body>
@if(empty($downloadMode))
<div class="actions">
    <button onclick="window.print()">Print / Save as PDF</button>
    @if(auth()->user() && in_array(auth()->user()->role,['admin','manager'],true))
        <a href="{{ route('admin.report-cards.download',[$student->id,$exam->id]) }}">Download report card</a>
    @else
        <a href="{{ route('portal.report-cards.download',[$student->id,$exam->id]) }}">Download report card</a>
    @endif
</div>
@endif

<main class="sheet">
    <header class="header">
        <div class="header-top">
            <div class="school-mark"><span>SM</span></div>
            <h1 class="school-name">{{ $schoolName }}</h1>
            <p class="school-subtitle">Competency Based Assessment • Learner Progress Report</p>
            <span class="document-label">Official Academic Record</span>
        </div>
        <table class="header-meta">
            <tr>
                <td><small>Assessment</small><strong>{{ $exam->name }}</strong></td>
                <td><small>Term</small><strong>{{ $exam->term }}</strong></td>
                <td><small>Academic Year</small><strong>{{ $exam->academic_year }}</strong></td>
                <td><small>Status</small><strong class="{{ $complete ? 'status-complete' : 'status-incomplete' }}">{{ $complete ? 'Complete' : 'Incomplete' }}</strong></td>
            </tr>
        </table>
    </header>

    <section class="title">
        <h1>COMPETENCY BASED CURRICULUM REPORT CARD</h1>
        <p>Term {{ $exam->term }} Assessment • {{ $exam->academic_year }}</p>
    </section>

    <table class="identity">
        <tr>
            <td><small>Learner Name</small><strong>{{ $student->name }}</strong></td>
            <td><small>Admission Number</small><strong>{{ $student->admission_number }}</strong></td>
            <td><small>Grade / Class</small><strong>{{ $student->class_label ?: $student->class_name ?: '—' }}{{ $student->class_stream ? ' - '.$student->class_stream : '' }}</strong></td>
            <td><small>Report Status</small><strong class="{{ $complete ? 'status-complete' : 'status-incomplete' }}">{{ $complete ? 'Complete' : 'Incomplete' }}</strong></td>
        </tr>
    </table>

    <div class="section-title">Learning Area Performance</div>
    <table class="table">
        <thead>
            <tr><th>Learning Area</th><th>Score</th><th>Achievement Level</th><th>Points</th><th>Teacher / Assessor Remark</th></tr>
        </thead>
        <tbody>
        @foreach($results as $result)
            <tr>
                <td><strong>{{ $result->subject_name }}</strong>@if($result->subject_code)<br><span class="subject-code">{{ $result->subject_code }}</span>@endif</td>
                <td>{{ $result->marks===null?'—':number_format((float)$result->marks,2) }}</td>
                <td class="{{ $result->assessment_status==='missed'?'missed':'level' }}">{{ $result->assessment_status==='missed'?'MISSED':$result->achievement_level }}</td>
                <td>{{ $result->achievement_points ?: '—' }}</td>
                <td>{{ $result->remarks ?: $result->display_remark }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><b>{{ $results->count() }}</b><span>Learning Areas</span></td>
            <td><b>{{ $results->where('assessment_status','missed')->count() }}</b><span>Missed Assessments</span></td>
            <td><b>{{ $points ?: '—' }}</b><span>Achievement Points</span></td>
            <td><b>{{ $average !== null ? number_format($average,1).'%' : '—' }}</b><span>Average Score</span></td>
        </tr>
    </table>

    <table class="two-col">
        <tr>
            <td>
                <div class="panel">
                    <h3>CBC Achievement Scale</h3>
                    <table class="scale">
                        <tr><td>EE</td><td>Exceeding Expectation</td></tr>
                        <tr><td>ME</td><td>Meeting Expectation</td></tr>
                        <tr><td>AE</td><td>Approaching Expectation</td></tr>
                        <tr><td>BE</td><td>Below Expectation</td></tr>
                    </table>
                    <p style="margin-top:7px">Performance levels: EE1, EE2, ME1, ME2, AE1, AE2, BE1 and BE2.</p>
                </div>
            </td>
            <td>
                <div class="panel">
                    <h3>Attendance Record</h3>
                    <table class="attendance">
                        <tr><td>Present</td><td>{{ $attendance['present'] ?? 0 }}</td></tr>
                        <tr><td>Absent</td><td>{{ $attendance['absent'] ?? 0 }}</td></tr>
                        <tr><td>Late</td><td>{{ $attendance['late'] ?? 0 }}</td></tr>
                        <tr><td>Excused</td><td>{{ $attendance['excused'] ?? 0 }}</td></tr>
                    </table>
                    @if($parent)<p><strong>Parent / Guardian:</strong> {{ $parent->name }}</p>@endif
                </div>
            </td>
        </tr>
    </table>

    <div class="note"><strong>Important:</strong> Missed assessments are explicitly recorded as MISSED and are not treated as zero marks. Achievement points are awarded only where a valid performance level is recorded.</div>

    <table class="signatures">
        <tr>
            <td>Class Teacher / Assessor<small>Signature &amp; Date</small></td>
            <td>Parent / Guardian<small>Signature &amp; Date</small></td>
            <td>Head of Institution<small>Signature &amp; Official Stamp</small></td>
        </tr>
    </table>

    <div class="footer">Generated by School Manager • Competency Based Assessment Report • This document is system generated.</div>
</main>
</body>
</html>
