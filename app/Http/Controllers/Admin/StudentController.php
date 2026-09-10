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
        $query = DB::table('students')
            ->leftJoin('parents', 'parents.id', '=', 'students.parent_id')
            ->leftJoin('school_classes', 'school_classes.id', '=', 'students.class_id')
            ->select('students.*', 'parents.name as linked_parent_name', 'school_classes.name as linked_class_name', 'school_classes.stream as linked_class_stream')
            ->orderByDesc('students.id');
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('students.name', 'like', "%{$search}%")
                    ->orWhere('students.admission_number', 'like', "%{$search}%")
                    ->orWhere('students.class_name', 'like', "%{$search}%")
                    ->orWhere('parents.name', 'like', "%{$search}%")
                    ->orWhere('students.parent_phone', 'like', "%{$search}%");
            });
        }
        $students = $query->paginate(10)->withQueryString();
        return view('admin.students.index', compact('students'));
    }

    public function create()
    {
        return view('admin.students.form', [
            'student' => null,
            'parents' => DB::table('parents')->orderBy('name')->get(),
            'classes' => DB::table('school_classes')->orderBy('name')->orderBy('stream')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->syncLegacyClassName($data);
        DB::table('students')->insert($data + ['fee_balance' => $data['fee_balance'] ?? 0, 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function edit($student)
    {
        $student = DB::table('students')->find($student);
        abort_unless($student, 404);
        return view('admin.students.form', [
            'student' => $student,
            'parents' => DB::table('parents')->orderBy('name')->get(),
            'classes' => DB::table('school_classes')->orderBy('name')->orderBy('stream')->get(),
        ]);
    }

    public function update(Request $request, $student)
    {
        $studentRecord = DB::table('students')->find($student);
        abort_unless($studentRecord, 404);
        $data = $this->validated($request, $student);
        $this->syncLegacyClassName($data);
        unset($data['fee_balance']);
        DB::table('students')->where('id', $student)->update($data + ['updated_at' => now()]);
        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully. Fee balances can only change through recorded fee transactions.');
    }

    public function destroy($student)
    {
        abort_unless(DB::table('students')->where('id', $student)->exists(), 404);
        try {
            DB::table('students')->where('id', $student)->delete();
            return back()->with('success', 'Student deleted successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors(['student' => 'This student cannot be deleted because related records exist.']);
        }
    }

    private function validated(Request $request, $student = null)
    {
        return $request->validate([
            'admission_number' => ['required','string','max:50', Rule::unique('students','admission_number')->ignore($student)],
            'name' => ['required','string','max:150'],
            'class_id' => ['nullable','exists:school_classes,id'],
            'parent_id' => ['nullable','exists:parents,id'],
            'class_name' => ['nullable','string','max:100'],
            'parent_name' => ['nullable','string','max:150'],
            'parent_phone' => ['nullable','regex:/^\+?2547\d{8}$/'],
            'fee_balance' => ['nullable','numeric','min:0'],
        ]);
    }

    private function syncLegacyClassName(array &$data)
    {
        if (!empty($data['class_id'])) {
            $class = DB::table('school_classes')->find($data['class_id']);
            if ($class) {
                $data['class_name'] = trim($class->name . ($class->stream ? ' - ' . $class->stream : ''));
            }
        }
    }
}
