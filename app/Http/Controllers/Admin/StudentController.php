<?php

namespace App\Http\Controllers\Admin;

// Import the base controller that Laravel controllers extend.
use App\Http\Controllers\Controller;
// Request gives access to submitted form fields and query-string filters.
use Illuminate\Http\Request;
// DB provides Laravel's query builder for reading and writing database records.
use Illuminate\Support\Facades\DB;
// Rule lets us define validation rules that need additional database conditions.
use Illuminate\Validation\Rule;

/**
 * Handles Feature 1: student records.
 *
 * PRESENTATION SUMMARY:
 * This controller is the middle layer between the student-management pages
 * and the database. It lists, creates, displays, updates, and archives students.
 *
 * Validation is performed before data is saved. Fee balances are deliberately
 * not editable through this controller: recorded fee transactions own that value.
 */
class StudentController extends Controller
{
    /**
     * Display the student list with search, class, and fee-balance filters.
     *
     * The Request parameter contains the filters submitted by the user.
     * The method builds a database query, runs it, and sends the results to a view.
     */
    public function index(Request $request)
    {
        // Start a query against the students table; no database rows are fetched yet.
        // A query builder lets us add optional filters before executing the query.
        $query = DB::table('students')
            // LEFT JOIN attaches parent details when a matching parent exists.
            // LEFT JOIN is used so a student still appears if no parent is linked.
            ->leftJoin('parents', function ($join) {
                // Match the parent's primary key to the student's parent_id foreign key.
                $join->on('parents.id', '=', 'students.parent_id')
                    // Ignore archived parents when displaying the linked-parent details.
                    ->whereNull('parents.archived_at');
            })
            // Attach class details in the same query to avoid looking up each class separately.
            ->leftJoin('school_classes', function ($join) {
                // Match the class table's ID with the class ID stored on the student.
                $join->on('school_classes.id', '=', 'students.class_id')
                    // Archived classes are not treated as active linked classes.
                    ->whereNull('school_classes.archived_at');
            })
            // Exclude archived students from the normal student list.
            ->whereNull('students.archived_at')
            // Select all student columns plus clearly named fields from joined tables.
            ->select(
                'students.*',
                'parents.name as linked_parent_name',
                'school_classes.name as linked_class_name',
                'school_classes.stream as linked_class_stream',
                'school_classes.academic_year as linked_class_year'
            )
            // Show the newest/highest-ID student records first.
            ->orderByDesc('students.id');

        // Only apply text search if the user supplied a non-empty search value.
        if ($request->filled('search')) {
            // Trim spaces around the search phrase before using it in the query.
            $search = trim((string) $request->input('search'));
            // Keep digits only as an extra way to match phone numbers typed with punctuation.
            $digits = preg_replace('/\D+/', '', $search);

            // Group OR conditions so the search phrase can match any supported field.
            // Grouping keeps these OR conditions together with the other filters.
            $query->where(function ($q) use ($search, $digits) {
                // Search the student's name for the supplied phrase.
                $q->where('students.name', 'like', "%{$search}%")
                    // Also search by admission number.
                    ->orWhere('students.admission_number', 'like', "%{$search}%")
                    // Search the class name saved on the student record.
                    ->orWhere('students.class_name', 'like', "%{$search}%")
                    // Search the linked parent's name.
                    ->orWhere('parents.name', 'like', "%{$search}%")
                    // Search the parent's phone number as originally stored.
                    ->orWhere('students.parent_phone', 'like', "%{$search}%");

                // If removing punctuation changed the search value, try the digits-only value too.
                if ($digits && $digits !== $search) {
                    $q->orWhere('students.parent_phone', 'like', "%{$digits}%");
                }
            });
        }

        // Apply the class filter only when class_id is present and contains digits only.
        // ctype_digit prevents non-numeric text from being treated as a class ID.
        if ($request->filled('class_id') && ctype_digit((string) $request->input('class_id'))) {
            // Convert the validated numeric string to an integer before querying.
            $query->where('students.class_id', (int) $request->input('class_id'));
        }

        // The 'clear' option means students have no positive outstanding fee balance.
        if ($request->input('balance') === 'clear') {
            $query->where('students.fee_balance', '<=', 0);
        // The 'outstanding' option means students still owe fees.
        } elseif ($request->input('balance') === 'outstanding') {
            $query->where('students.fee_balance', '>', 0);
        }

        // Return at most 10 students per page instead of loading the entire table.
        // withQueryString keeps search/filter parameters in pagination links.
        $students = $query->paginate(10)->withQueryString();

        // Load active classes for the class-filter dropdown in the student list.
        $classes = DB::table('school_classes')
            // Exclude archived classes from the dropdown.
            ->whereNull('archived_at')
            // Put the newest academic year first.
            ->orderByDesc('academic_year')
            // Sort classes by name, then by stream for easier selection.
            ->orderBy('name')
            ->orderBy('stream')
            // Execute the query and return the matching class rows as a collection.
            ->get();

        // Render the student-list Blade view and make both variables available to it.
        // compact('students', 'classes') creates an array keyed by those variable names.
        return view('admin.students.index', compact('students', 'classes'));
    }

    /**
     * Show the blank form used to register a new student.
     *
     * No student has been saved at this stage; this method only prepares view data.
     */
    public function create()
    {
        // Render the shared student form; null tells the view this is a new record.
        return view('admin.students.form', [
            'student' => null,
            // Load active parents alphabetically for the parent-selection control.
            'parents' => DB::table('parents')->whereNull('archived_at')->orderBy('name')->get(),
            // Load active classes and sort them for the class-selection control.
            'classes' => DB::table('school_classes')->whereNull('archived_at')->orderBy('name')->orderBy('stream')->get(),
        ]);
    }

    /**
     * Validate and save a newly submitted student record.
     *
     * This method is called when the registration form is submitted.
     */
    public function store(Request $request)
    {
        // Validate all accepted fields before attempting to insert a database row.
        $data = $this->validated($request);
        // Keep the older class_name field consistent with the selected class record.
        $this->syncLegacyClassName($data);
        // Convert a supported Kenyan phone format to a consistent international format.
        $data['parent_phone'] = $this->normalizeKenyanPhone($data['parent_phone'] ?? null);

        // Do not accept a user-supplied fee balance from the student form.
        // unset removes this key from the array before the insert is prepared.
        // A new student's starting balance is set explicitly to zero below.
        unset($data['fee_balance']);

        // Insert the validated student details into the students table.
        // The + array operator adds these default fields without overwriting existing keys.
        DB::table('students')->insert($data + [
            // Start the new student with a zero balance; payment records manage later changes.
            'fee_balance' => 0,
            // Record when this row was created.
            'created_at' => now(),
            // Record the initial last-updated time.
            'updated_at' => now(),
        ]);

        // Return to the student list and display a one-time success message.
        return redirect()->route('admin.students.index')->with('success', 'Student added successfully.');
    }

    /**
     * Show the edit form for an existing, non-archived student.
     *
     * The $student argument is the identifier supplied by the route.
     */
    public function edit($student)
    {
        // Find the requested student, but do not allow archived records to be edited here.
        $student = DB::table('students')->whereNull('archived_at')->find($student);

        // If the record does not exist or is archived, stop and return HTTP 404 Not Found.
        abort_unless($student, 404);

        // Reuse the same form view as creation, this time filling it with existing data.
        return view('admin.students.form', [
            // The view uses this object to pre-fill the form fields.
            'student' => $student,
            // Supply active parents so the user can change the linked parent if needed.
            'parents' => DB::table('parents')->whereNull('archived_at')->orderBy('name')->get(),
            // Supply active classes for the class-selection control.
            'classes' => DB::table('school_classes')->whereNull('archived_at')->orderBy('name')->orderBy('stream')->get(),
        ]);
    }

    /**
     * Validate and save changes to an existing student.
     *
     * Unlike store(), this method passes the current ID to validation so the
     * student's existing admission number does not conflict with itself.
     */
    public function update(Request $request, $student)
    {
        // Load only a non-archived student before allowing changes.
        $studentRecord = DB::table('students')->whereNull('archived_at')->find($student);

        // Protect against missing IDs and attempts to update archived records.
        abort_unless($studentRecord, 404);

        // Validate the submitted values; the current student ID is used for uniqueness checks.
        $data = $this->validated($request, $student);
        // Synchronize the readable legacy class name with the selected class.
        $this->syncLegacyClassName($data);
        // Normalize the parent phone number if it matches a supported Kenyan format.
        $data['parent_phone'] = $this->normalizeKenyanPhone($data['parent_phone'] ?? null);

        // Prevent the submitted form from changing the stored fee balance.
        unset($data['fee_balance']);

        // Update the matching student's row and refresh its updated_at timestamp.
        DB::table('students')->where('id', $student)->update($data + ['updated_at' => now()]);

        // Redirect to the list and explain that fee changes must come from transactions.
        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully. Fee balances can only change through recorded fee transactions.');
    }

    /**
     * Archive a student without permanently deleting the database row.
     *
     * Soft archiving preserves the student's history for financial and academic reference.
     */
    public function destroy($student)
    {
        // Confirm that the requested student exists and has not already been archived.
        $studentRecord = DB::table('students')->whereNull('archived_at')->find($student);

        // Return 404 if no active record matches the supplied ID.
        abort_unless($studentRecord, 404);

        // Mark the record as archived rather than deleting it from the database.
        DB::table('students')->where('id', $student)->update([
            // A non-null timestamp is the application's indication that the record is archived.
            'archived_at' => now(),
            // Track when the archive action changed the record.
            'updated_at' => now(),
        ]);

        // Return to the previous page and display a confirmation message.
        return back()->with('success', 'Student record archived. Financial, attendance and academic history has been preserved.');
    }

    /**
     * Validate student form fields for both create and update operations.
     *
     * Reusing one method prevents the two forms from having inconsistent rules.
     * The optional $student ID is ignored when checking admission-number uniqueness.
     */
    private function validated(Request $request, $student = null)
    {
        // Laravel checks these rules and automatically redirects back with errors if validation fails.
        return $request->validate([
            // Admission number is mandatory, must be text, is limited to 50 characters,
            // and must be unique in the students table except for the current record on update.
            'admission_number' => ['required', 'string', 'max:50', Rule::unique('students', 'admission_number')->ignore($student)],

            // Student name is mandatory text with a maximum length of 150 characters.
            'name' => ['required', 'string', 'max:150'],

            // A class may be left unselected; if selected, it must reference an active class.
            'class_id' => [
                'nullable',
                Rule::exists('school_classes', 'id')->where(function ($query) {
                    // Only accept a class whose archived_at field is null.
                    return $query->whereNull('archived_at');
                }),
            ],

            // A parent link is optional; if supplied, it must reference an active parent.
            'parent_id' => [
                'nullable',
                Rule::exists('parents', 'id')->where(function ($query) {
                    // Reject IDs belonging to archived parent records.
                    return $query->whereNull('archived_at');
                }),
            ],

            // Legacy text class field is optional and limited to 100 characters.
            'class_name' => ['nullable', 'string', 'max:100'],

            // Parent or guardian name is optional and limited to 150 characters.
            'parent_name' => ['nullable', 'string', 'max:150'],

            // Parent phone is optional; the regex accepts Kenyan numbers beginning 07 or 254/+254,
            // followed by 7 and eight more digits (for example, 0712345678 or +254712345678).
            'parent_phone' => ['nullable', 'string', 'max:20', 'regex:/^(?:0|\+?254)7\d{8}$/'],

            // A supplied balance must be numeric and non-negative, although store/update remove it
            // before saving. This rule may support form validation but does not authorize balance edits.
            'fee_balance' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    /**
     * Keep the legacy class_name text field synchronized with class_id.
     *
     * Some older parts of the application may still read class_name even though
     * the newer relationship uses class_id to identify the class.
     *
     * The array is passed by reference (&), so changes made here affect $data
     * in the calling store() or update() method.
     */
    private function syncLegacyClassName(array &$data): void
    {
        // Only look up a class if the form contains a non-empty class ID.
        if (!empty($data['class_id'])) {
            // Fetch the selected active class from the database.
            $class = DB::table('school_classes')->whereNull('archived_at')->find($data['class_id']);

            // A missing or archived class is not used to build a display name.
            if ($class) {
                // Combine the class name and stream when a stream exists, then trim outer spaces.
                // Example: name "Form 2" and stream "East" becomes "Form 2 - East".
                $data['class_name'] = trim($class->name . ($class->stream ? ' - ' . $class->stream : ''));
            }
        }
    }

    /**
     * Normalize common Kenyan parent phone-number formats.
     *
     * Returns null for an empty value. Supported formats are converted to +254...
     * If the number does not match a recognized format, the original value is returned;
     * validation is handled separately by validated().
     */
    private function normalizeKenyanPhone(?string $phone): ?string
    {
        // A nullable phone field with no value should remain null.
        if (!$phone) {
            return null;
        }

        // Remove spaces, plus signs, brackets, and other non-digit characters for comparison.
        $digits = preg_replace('/\D+/', '', $phone);

        // If the digits already start with Kenya's country code 254 and have 12 digits,
        // return the number with a leading plus sign, e.g. 254712345678 -> +254712345678.
        if (substr($digits, 0, 3) === '254' && strlen($digits) === 12) {
            return '+' . $digits;
        }

        // Convert a 10-digit local mobile number beginning 07 to the +254 international form.
        // Example: 0712345678 -> +254712345678.
        if (substr($digits, 0, 2) === '07' && strlen($digits) === 10) {
            return '+254' . substr($digits, 1);
        }

        // If the number did not match either supported normalization pattern, leave it unchanged.
        return $phone;
    }
}
