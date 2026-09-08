<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_name' => 'required|string|max:150',
            'date_of_birth' => 'required|date',
            'requested_class' => 'required|string|max:100',
            'parent_name' => 'required|string|max:150',
            'parent_phone' => ['required', 'regex:/^\+?2547\d{8}$/'],
            'parent_email' => 'nullable|email|max:150',
            'message' => 'nullable|string|max:2000',
        ]);

        $data['status'] = 'pending';
        DB::table('admission_applications')->insert($data + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Your admission application has been submitted successfully. The school will contact you.');
    }
}
