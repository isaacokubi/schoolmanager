<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('students')->orderByDesc('id');
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%")
                    ->orWhere('class_name', 'like', "%{$search}%")
                    ->orWhere('parent_phone', 'like', "%{$search}%");
            });
        }
        $students = $query->paginate(10)->withQueryString();
        return view('admin.students.index', compact('students'));
    }

    public function create()
    {
        return view('admin.students.form', ['student' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'admission_number' => ['required','string','max:50','unique:students,admission_number'],
            'name' => ['required','string','max:150'],
            'class_name' => ['nullable','string','max:100'],
            'parent_name' => ['nullable','string','max:150'],
            'parent_phone' => ['nullable','regex:/^\\+?2547\\d{8}$/'],
            'fee_balance' => ['nullable','numeric','min:0'],
        ]);
        DB::table('students')->insert($data + ['fee_balance' => $data['fee_balance'] ?? 0, 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function edit($student)
    {
        $student = DB::table('students')->find($student);
        abort_unless($student, 404);
        return view('admin.students.form', compact('student'));
    }

    public function update(Request $request, $student)
    {
        $studentRecord = DB::table('students')->find($student);
        abort_unless($studentRecord, 404);
        $data = $request->validate([
            'admission_number' => ['required','string','max:50',Rule::unique('students','admission_number')->ignore($student)],
            'name' => ['required','string','max:150'],
            'class_name' => ['nullable','string','max:100'],
            'parent_name' => ['nullable','string','max:150'],
            'parent_phone' => ['nullable','regex:/^\\+?2547\\d{8}$/'],
            'fee_balance' => ['nullable','numeric','min:0'],
        ]);
        DB::table('students')->where('id', $student)->update($data + ['updated_at' => now()]);
        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy($student)
    {
        DB::table('students')->where('id', $student)->delete();
        return back()->with('success', 'Student deleted successfully.');
    }
}
