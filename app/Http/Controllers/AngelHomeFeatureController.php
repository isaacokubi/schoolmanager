<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AngelHomeFeatureController extends Controller
{
    private $features = [
        'learning' => 'Digital Learning',
        'library' => 'Library',
        'notifications' => 'Notifications',
        'communications' => 'Communications',
        'timetable' => 'Timetable',
        'lesson_plans' => 'Lesson Plans',
        'academic_years' => 'Academic Years',
        'class_requests' => 'Class Requests',
        'student_records' => 'Student Academic Records',
        'inventory' => 'Inventory',
        'transport' => 'Transport',
        'meals' => 'Meal Plans',
        'school_events' => 'School Events',
        'online_classroom' => 'Online Classroom',
    ];

    public function admin(Request $request)
    {
        $feature = $request->get('feature', 'learning');
        abort_unless(isset($this->features[$feature]), 404);

        $query = DB::table('school_feature_records')->where('feature', $feature)->whereNull('archived_at');
        if ($request->filled('search')) {
            $term = trim((string) $request->get('search'));
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                  ->orWhere('status', 'like', '%' . $term . '%')
                  ->orWhere('audience', 'like', '%' . $term . '%');
            });
        }

        $records = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $stats = [
            'total' => DB::table('school_feature_records')->where('feature', $feature)->whereNull('archived_at')->count(),
            'published' => DB::table('school_feature_records')->where('feature', $feature)->whereNull('archived_at')->where('published', true)->count(),
            'active' => DB::table('school_feature_records')->where('feature', $feature)->whereNull('archived_at')->where('status', 'active')->count(),
        ];

        return view('admin.features', ['features' => $this->features, 'feature' => $feature, 'records' => $records, 'stats' => $stats]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRecord($request);
        $id = DB::table('school_feature_records')->insertGetId([
            'feature' => $data['feature'],
            'title' => $data['title'],
            'status' => $data['status'],
            'audience' => $data['audience'],
            'created_by' => $request->user()->id,
            'student_id' => $data['student_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'published' => $request->boolean('published', true),
            'payload' => json_encode($this->payload($request)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.features', ['feature' => $data['feature']])->with('success', 'Feature record created successfully (#' . $id . ').');
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validateRecord($request);
        abort_unless(DB::table('school_feature_records')->where('id', $id)->whereNull('archived_at')->exists(), 404);
        DB::table('school_feature_records')->where('id', $id)->update([
            'feature' => $data['feature'],
            'title' => $data['title'],
            'status' => $data['status'],
            'audience' => $data['audience'],
            'student_id' => $data['student_id'] ?? null,
            'class_id' => $data['class_id'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'published' => $request->boolean('published', true),
            'payload' => json_encode($this->payload($request)),
            'updated_at' => now(),
        ]);
        return redirect()->route('admin.features', ['feature' => $data['feature']])->with('success', 'Feature record updated successfully.');
    }

    public function archive(int $id)
    {
        abort_unless(DB::table('school_feature_records')->where('id', $id)->whereNull('archived_at')->exists(), 404);
        $feature = DB::table('school_feature_records')->where('id', $id)->value('feature');
        DB::table('school_feature_records')->where('id', $id)->update(['archived_at' => now(), 'updated_at' => now()]);
        return redirect()->route('admin.features', ['feature' => $feature])->with('success', 'Record archived successfully.');
    }

    public function portal(Request $request)
    {
        $role = strtolower((string) ($request->user()->role ?? 'pupil'));
        $allowed = ['admin', 'teacher', 'pupil', 'parent', 'sponsor', 'manager', 'operations_manager', 'operations-manager'];
        abort_unless(in_array($role, $allowed, true), 403);

        $features = in_array($role, ['admin', 'manager', 'operations_manager', 'operations-manager'], true)
            ? array_keys($this->features)
            : ['learning', 'library', 'notifications', 'communications', 'timetable', 'lesson_plans', 'student_records', 'online_classroom', 'school_events'];

        $records = DB::table('school_feature_records')
            ->whereNull('archived_at')
            ->where('published', true)
            ->whereIn('feature', $features)
            ->where(function ($q) use ($role) {
                $q->where('audience', 'all')->orWhere('audience', $role);
            })
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('portal.features', compact('records', 'features', 'role'));
    }

    private function validateRecord(Request $request)
    {
        return $request->validate([
            'feature' => ['required', Rule::in(array_keys($this->features))],
            'title' => 'required|string|max:220',
            'status' => 'required|string|max:30',
            'audience' => ['required', Rule::in(['all', 'admin', 'teacher', 'pupil', 'parent', 'sponsor'])],
            'student_id' => 'nullable|exists:students,id',
            'class_id' => 'nullable|exists:school_classes,id',
            'starts_at' => 'nullable|date',
            'due_at' => 'nullable|date',
        ]);
    }

    private function payload(Request $request)
    {
        $raw = trim((string) $request->input('payload', ''));
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) abort(422, 'Payload must be valid JSON.');
        return $decoded;
    }
}