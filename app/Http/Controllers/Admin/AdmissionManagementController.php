<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * Controller for the admission management workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * logic, and returns responses or views. The method comments below explain
 * the purpose of each action and how it fits into the workflow.
 */

class AdmissionManagementController extends Controller
{
    /**
     * Prepare the data needed by the page, apply any supported filters, and return the rendered listing or dashboard.
     * Any request filters are read from the Request object and applied before data is returned.
     */
    public function index(Request $request)
    {
        $query = DB::table('admission_applications')->orderByDesc('id');
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('student_name','like',"%{$search}%")
                  ->orWhere('parent_name','like',"%{$search}%")
                  ->orWhere('parent_phone','like',"%{$search}%");
            });
        }

        $applications = $query->paginate(10)->withQueryString();
        $statusCounts = DB::table('admission_applications')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        // Render the Blade template and pass it the data prepared by this controller.
        return view('admin.admissions.index', compact('applications', 'statusCounts'));
    }

    /**
     * Validate the requested status transition and save the new status for the selected record.
     * Validate user-controlled input before writing changes to the database.
     */
    public function updateStatus(Request $request, $application)
    {
        $data = $request->validate(['status' => ['required','in:pending,approved,rejected']]);
        $record = DB::table('admission_applications')->find($application);
        // Stop the request with the specified HTTP error when the required condition is not met.
        abort_unless($record, 404);

        // Run related database writes in one transaction so they succeed or roll back together.
        DB::transaction(function () use ($data, $record, $application) {
            // Start a query against the named database table; chained calls add conditions or perform the operation.
            DB::table('admission_applications')
                ->where('id', $application)
                ->update(['status' => $data['status'], 'updated_at' => now()]);

            if ($data['status'] !== 'approved') return;

            $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $record->student_name), 0, 4)) ?: 'STUD';
            $admission = $base . '-' . date('Y') . '-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);

            if (DB::table('students')->where('admission_number', $admission)->exists()) return;

            while (DB::table('students')->where('admission_number', $admission)->exists()) {
                $admission .= 'X';
            }

            DB::table('students')->insert([
                'admission_number' => $admission,
                'name' => $record->student_name,
                'class_name' => $record->requested_class,
                'parent_name' => $record->parent_name,
                'parent_phone' => $record->parent_phone,
                'fee_balance' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success','Admission status updated successfully.');
    }
}
