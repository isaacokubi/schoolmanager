<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
/**
 * Controller for the angel home feature workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * services, and returns views or HTTP responses. The method comments explain
 * the purpose of each action and its role in the workflow.
 */

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

    /**
     * Helper method for AngelHomeFeatureController.php; it performs the admin step and returns or updates data used by its caller.
     */
    public function admin(Request $request)
    {
        $feature = $request->get('feature', 'learning');
        // Stop with the specified HTTP error when the required condition is false.
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

        // Render the Blade template and pass it the data prepared by this controller.
        return view('admin.features', ['features' => $this->features, 'feature' => $feature, 'records' => $records, 'stats' => $stats]);
    }

    /**
     * Validate submitted information, perform the required operation, and return a response.
     */
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

        // Redirect the browser to the next route and attach a one-time status message if supplied.
        return redirect()->route('admin.features', ['feature' => $data['feature']])->with('success', 'Feature record created successfully (#' . $id . ').');
    }

    /**
     * Validate and apply the requested changes to the selected record.
     */
    public function update(Request $request, int $id)
    {
        $data = $this->validateRecord($request);
        // Stop with the specified HTTP error when the required condition is false.
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
        // Redirect the browser to the next route and attach a one-time status message if supplied.
        return redirect()->route('admin.features', ['feature' => $data['feature']])->with('success', 'Feature record updated successfully.');
    }

    /**
     * Helper method for AngelHomeFeatureController.php; it performs the archive step and returns or updates data used by its caller.
     */
    public function archive(int $id)
    {
        // Stop with the specified HTTP error when the required condition is false.
        abort_unless(DB::table('school_feature_records')->where('id', $id)->whereNull('archived_at')->exists(), 404);
        $feature = DB::table('school_feature_records')->where('id', $id)->value('feature');
        DB::table('school_feature_records')->where('id', $id)->update(['archived_at' => now(), 'updated_at' => now()]);
        // Redirect the browser to the next route and attach a one-time status message if supplied.
        return redirect()->route('admin.features', ['feature' => $feature])->with('success', 'Record archived successfully.');
    }

    /**
     * Helper method for AngelHomeFeatureController.php; it performs the portal step and returns or updates data used by its caller.
     */
    public function portal(Request $request)
    {
        $role = strtolower((string) ($request->user()->role ?? 'pupil'));
        $allowed = ['admin', 'teacher', 'pupil', 'parent', 'sponsor', 'manager', 'operations_manager', 'operations-manager'];
        // Stop with the specified HTTP error when the required condition is false.
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

        // Render the Blade template and pass it the data prepared by this controller.
        return view('portal.features', compact('records', 'features', 'role'));
    }

    /**
     * Validate the supplied values before the workflow proceeds.
     */
    private function validateRecord(Request $request)
    {
        // Validate request input before this workflow uses it or writes changes to the database.
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

    /**
     * Helper method for AngelHomeFeatureController.php; it performs the payload step and returns or updates data used by its caller.
     */
    private function payload(Request $request)
    {
        $raw = trim((string) $request->input('payload', ''));
        if ($raw === '') return [];
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) abort(422, 'Payload must be valid JSON.');
        return $decoded;
    }
}