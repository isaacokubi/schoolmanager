<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionManagementController extends Controller
{
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
        return view('admin.admissions.index', compact('applications'));
    }

    public function updateStatus(Request $request, $application)
    {
        $data = $request->validate(['status' => ['required','in:pending,approved,rejected']]);
        $record = DB::table('admission_applications')->find($application);
        abort_unless($record, 404);

        DB::transaction(function () use ($data, $record, $application) {
            DB::table('admission_applications')
                ->where('id', $application)
                ->update(['status' => $data['status'], 'updated_at' => now()]);

            if ($data['status'] !== 'approved') return;

            // Approval is idempotent: approving the same application again must not
            // create a second learner record.
            $alreadyLinked = DB::table('students')
                ->where('admission_number', 'ADM-' . date('Y') . '-' . str_pad((string) $record->id, 6, '0', STR_PAD_LEFT))
                ->exists();
            if ($alreadyLinked) return;

            $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $record->student_name), 0, 4)) ?: 'STUD';
            $admission = $base . '-' . date('Y') . '-' . str_pad((string) $record->id, 4, '0', STR_PAD_LEFT);
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
