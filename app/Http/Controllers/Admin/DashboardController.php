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
            'parents' => $this->countTable('parents'),
            'teachers' => $this->countTable('teachers'),
            'classes' => $this->countTable('school_classes'),
            'attendance_today' => $this->countWhere('attendance', 'attendance_date', now()->toDateString()),
            'published_announcements' => $this->countWhere('announcements', 'published', 1),
        ];

        $paymentTotal = 0;
        $outstanding = 0;
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing('payments');
            foreach (['amount', 'payment_amount', 'paid_amount'] as $column) {
                if (in_array($column, $columns, true)) {
                    $paymentTotal = (float) DB::table('payments')->where(function ($q) {
                        $q->whereNull('status')->orWhereIn('status', ['completed', 'paid', 'success']);
                    })->sum($column);
                    break;
                }
            }
        } catch (\Throwable $e) {
            $paymentTotal = 0;
        }

        try {
            $studentColumns = DB::getSchemaBuilder()->getColumnListing('students');
            foreach (['fee_balance', 'balance', 'outstanding_balance'] as $column) {
                if (in_array($column, $studentColumns, true)) {
                    $outstanding = (float) DB::table('students')->sum($column);
                    break;
                }
            }
        } catch (\Throwable $e) {
            $outstanding = 0;
        }

        $stats['payment_total'] = $paymentTotal;
        $stats['outstanding'] = $outstanding;
        $recentApplications = DB::table('admission_applications')->latest()->limit(5)->get();
        $recentPayments = DB::table('payments')->latest()->limit(5)->get();
        $upcomingEvents = $this->safeQuery('events', function ($q) {
            return $q->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date')->limit(5)->get();
        });

        return view('admin.dashboard', compact('stats', 'recentApplications', 'recentPayments', 'upcomingEvents'));
    }

    private function countTable($table)
    {
        try { return DB::table($table)->count(); } catch (\Throwable $e) { return 0; }
    }

    private function countWhere($table, $column, $value)
    {
        try { return DB::table($table)->where($column, $value)->count(); } catch (\Throwable $e) { return 0; }
    }

    private function safeQuery($table, $callback)
    {
        try { return $callback(DB::table($table)); } catch (\Throwable $e) { return collect(); }
    }
}
