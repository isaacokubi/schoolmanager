<?php

namespace App\Http\Controllers;

use App\Services\CbcReportCardService;
use Barryvdh\DomPDF\Facade as Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReportCardController extends Controller
{
    public function show(Request $request, CbcReportCardService $service, int $student, int $exam)
    {
        $report = $service->build($student, $exam);