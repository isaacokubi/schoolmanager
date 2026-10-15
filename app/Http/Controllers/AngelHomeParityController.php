<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * Controller for the angel home parity workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * services, and returns views or HTTP responses. The method comments explain
 * the purpose of each action and its role in the workflow.
 */

class AngelHomeParityController extends Controller
{
    /**
     * Helper method for AngelHomeParityController.php; it performs the donations step and returns or updates data used by its caller.
     */
    public function donations()
    {
        $settings = $this->settings();
        // Render the Blade template and pass it the data prepared by this controller.
        return view('pages.donations', compact('settings'));
    }

    /**
     * Helper method for AngelHomeParityController.php; it performs the storeDonation step and returns or updates data used by its caller.
     */
    public function storeDonation(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'email' => 'nullable|email|max:190',
            'phone' => 'nullable|string|max:40',
            'amount' => 'nullable|numeric|min:0|max:999999999',
            'purpose' => 'required|string|max:120',
            'message' => 'nullable|string|max:2000',
        ]);
        DB::table('school_donations')->insert($data + [
            'currency' => 'KES',
            'status' => 'pledged',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return back()->with('success', 'Thank you. Your support pledge has been received by the school.');
    }

    /**
     * Helper method for AngelHomeParityController.php; it performs the donationsAdmin step and returns or updates data used by its caller.
     */
    public function donationsAdmin(Request $request)
    {
        $query = DB::table('school_donations')->orderByDesc('id');
        if ($request->filled('status')) $query->where('status',$request->status);
        $donations = $query->paginate(25)->withQueryString();
        // Render the Blade template and pass it the data prepared by this controller.
        return view('admin.donations', compact('donations'));
    }

    /**
     * Validate submitted changes and update the relevant record.
     */
    public function updateDonation(Request $request, int $id)
    {
        $data = $request->validate(['status'=>'required|in:pledged,contacted,received,cancelled']);
        DB::table('school_donations')->where('id',$id)->update($data+['processed_by'=>$request->user()->id,'updated_at'=>now()]);
        return back()->with('success','Donation status updated.');
    }

    /**
     * Helper method for AngelHomeParityController.php; it performs the community step and returns or updates data used by its caller.
     */
    public function community(string $type)
    {
        // Stop with the specified HTTP error when the required condition is false.
        abort_unless(in_array($type, ['teachers','pupils','sponsors'], true), 404);
        $settings = $this->settings();
        if ($type === 'teachers') {
            $data = DB::table('teachers')->whereNull('archived_at')->orderBy('name')->get();
        } elseif ($type === 'pupils') {
            $data = DB::table('students')->whereNull('archived_at')->orderBy('name')->limit(100)->get();
        } else {
            $data = DB::table('parents')->orderBy('name')->limit(100)->get();
        }
        // Render the Blade template and pass it the data prepared by this controller.
        return view('pages.community', compact('settings','type','data'));
    }

    /**
     * Helper method for AngelHomeParityController.php; it performs the support step and returns or updates data used by its caller.
     */
    public function support()
    {
        $settings = $this->settings();
        // Render the Blade template and pass it the data prepared by this controller.
        return view('pages.support', compact('settings'));
    }

    /**
     * Helper method for AngelHomeParityController.php; it performs the featureAction step and returns or updates data used by its caller.
     */
    public function featureAction(Request $request, int $id, string $action)
    {
        $record = DB::table('school_feature_records')->where('id',$id)->whereNull('archived_at')->first();
        // Stop with the specified HTTP error when the required condition is false.
        abort_unless($record, 404);
        $role = strtolower((string)($request->user()->role ?? ''));
        // Stop with the specified HTTP error when the required condition is false.
        abort_unless(in_array($role, ['admin','teacher','pupil','parent','sponsor','manager','operations_manager','operations-manager'], true), 403);

        $payload = json_decode($record->payload ?: '{}', true) ?: [];
        if ($action === 'complete') {
            $payload['completed'] = true;
            $payload['completed_at'] = now()->toIso8601String();
        } elseif ($action === 'read') {
            $payload['read'] = true;
            $payload['read_at'] = now()->toIso8601String();
        } elseif ($action === 'borrow') {
            $payload['loan_status'] = 'borrowed';
            $payload['borrowed_at'] = now()->toIso8601String();
            $payload['borrowed_by'] = $request->user()->id;
        } elseif ($action === 'return') {
            $payload['loan_status'] = 'returned';
            $payload['returned_at'] = now()->toIso8601String();
        } else {
            abort(404);
        }

        DB::table('school_feature_records')->where('id',$id)->update([
            'payload' => json_encode($payload),
            'status' => $action === 'complete' ? 'completed' : ($action === 'return' ? 'returned' : ($record->status ?: 'active')),
            'updated_at' => now(),
        ]);
        return back()->with('success', ucfirst($action).' action completed.');
    }

    /**
     * Helper method for AngelHomeParityController.php; it performs the settings step and returns or updates data used by its caller.
     */
    private function settings(): array
    {
        $defaults = [
            'school_name'=>'School Manager','school_phone'=>'','school_email'=>'','school_address'=>'',
            'academic_year'=>date('Y'),'academic_term'=>'Term 1','currency'=>'KES',
            'mission'=>'To provide a safe, inclusive and inspiring learning environment where every learner can develop academically, socially and creatively.',
            'vision'=>'To nurture responsible, confident and capable young people prepared to contribute positively to society.',
        ];
        try { foreach(DB::table('settings')->get() as $setting) $defaults[$setting->key]=$setting->value; } catch(\Throwable $e) {}
        return $defaults;
    }
}