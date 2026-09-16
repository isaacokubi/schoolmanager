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
            ->leftJoin('parents', function ($join) {
                $join->on('parents.id', '=', 'students.parent_id')
                    ->whereNull('parents.archived_at');
            })
            ->leftJoin('school_classes', function ($join) {
                $join->on('school_classes.id', '=', 'students.class_id')
                    ->whereNull('school_classes.archived_at');
            })
            ->whereNull('students.archived_at')
            ->select(
                'students.*',
                'parents.name as linked_parent_name',
                'school_classes.name as linked_class_name',
                'school_classes.stream as linked_class_stream',
                'school_classes.academic_year as linked_class_year'
            )
            ->orderByDesc('students.id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $digits = preg_replace('/\D+/', '', $search);
            $query->where(function ($q) use ($search, $digits) {
                $q->where('students.name', 'like', "%{$search}%")
                    ->orWhere('students.admission_number', 'like', "%{$search}%")
                    ->orWhere('students.class_name', 'like', "%{$search}%")
                    ->orWhere('parents.name', 'like', "%{$search}%")
                    ->orWhere('students.parent_phone', 'like', "%{$search}%");

                if ($digits && $digits !== $search) {
                    $q->orWhere('students.parent_phone', 'like', "%{$digits}%");
                }
            });
        }

        if ($request->filled('class_id') && ctype_digit((string) $request->input('class_id'))) {
            $query->where('students.class_id', (int) $request->input('class_id'));
        }

        if ($request->input('balance') === 'clear') {
            $query->where('students.fee_balance', '<=', 0);
        } elseif ($request->input('balance') === 'outstanding') {
            $query->where('students.fee_balance', '>', 0);
        }

        $students = $query->paginate(10)->withQueryString();
        $classes = DB::table('school_classes')
            ->whereNull('archived_at')
            ->orderByDesc('academic_year')
            ->orderBy('name')
            ->orderBy('stream')
            ->get();

        return view('admin.students.index', compact('students', 'classes'));
    }

    public function create()
    {
        return view('admin.students.form', [
            'student' => null,
            'parents' => DB::table('parents')->whereNull('archived_at')->orderBy('name')->get(),
            'classes' => DB::table('school_classes')->whereNull('archived_at')->orderBy('name')->orderBy('stream')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->syncLegacyClassName($data);
        $data['parent_phone'] = $this->normalizeKenyanPhone($data['parent_phone'] ?? null);
        unset($data['fee_balance']);

        DB::table('students')->insert($data + [
            'fee_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    public function edit($student)
    {
        $student = DB::table('students')->whereNull('archived_at')->find($student);
        abort_unless($student, 404);

        return view('admin.students.form', [
            'student' => $student,
            'parents' => DB::table('parents')->whereNull('archived_at')->orderBy('name')->get(),
            'classes' => DB::table('school_classes')->whereNull('archived_at')->orderBy('name')->orderBy('stream')->get(),
        ]);
    }

    public function update(Request $request, $student)
    {
        $studentRecord = DB::table('students')->whereNull('archived_at')->find($student);
        abort_unless($studentRecord, 404);

        $data = $this->validated($request, $student);
        $this->syncLegacyClassName($data);
        $data['parent_phone'] = $this->normalizeKenyanPhone($data['parent_phone'] ?? null);
        unset($data['fee_balance']);

        DB::table('students')->where('id', $student)->update($data + ['updated_at' => now()]);

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully. Fee balances can only change through recorded fee transactions.');
    }

    public function destroy($student)
    {
        $studentRecord = DB::table('students')->whereNull('archived_at')->find($student);
        abort_unless($studentRecord, 404);

        DB::table('students')->where('id', $student)->update([
            'archived_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Student record archived. Financial, attendance and academic history has been preserved.');
    }

    private function validated(Request $request, $student = null)
    {
        return $request->validate([
            'admission_number' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_number')->ignore($student)],
            'name' => ['required', 'string', 'max:150'],
            'class_id' => [
                'nullable',
                Rule::exists('school_classes', 'id')->where(function ($query) {
                    return $query->whereNull('archived_at');
                }),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('parents', 'id')->where(function ($query) {
                    return $query->whereNull('archived_at');
                }),
            ],
            'class_name' => ['nullable', 'string', 'max:100'],
            'parent_name' => ['nullable', 'string', 'max:150'],
            'parent_phone' => ['nullable', 'string', 'max:20', 'regex:/^(?:0|\+?254)7\d{8}$/'],
            'fee_balance' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function syncLegacyClassName(array &$data): void
    {
        if (!empty($data['class_id'])) {
            $class = DB::table('school_classes')->whereNull('archived_at')->find($data['class_id']);
            if ($class) {
                $data['class_name'] = trim($class->name . ($class->stream ? ' - ' . $class->stream : ''));
            }
        }
    }

    private function normalizeKenyanPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if (substr($digits, 0, 3) === '254' && strlen($digits) === 12) {
            return '+' . $digits;
        }
        if (substr($digits, 0, 2) === '07' && strlen($digits) === 10) {
            return '+254' . substr($digits, 1);
        }

        return $phone;
    }
}
