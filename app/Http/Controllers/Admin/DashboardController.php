<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'students' => DB::table('students')->count(),
            'applications' => DB::table('admission_applications')->count(),
            'payments' => DB::table('payments')->count(),
        ];

        $paymentTotal = 0;
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing('payments');
            foreach (['amount', 'payment_amount', 'paid_amount'] as $column) {
                if (in_array($column, $columns, true)) {
                    $paymentTotal = (float) DB::table('payments')->sum($column);
                    break;
                }
            }
        } catch (\Throwable $e) {
            $paymentTotal = 0;
        }

        $stats['payment_total'] = $paymentTotal;
        $recentApplications = DB::table('admission_applications')->latest()->limit(5)->get();
        $recentPayments = DB::table('payments')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentApplications', 'recentPayments'));
    }
}
