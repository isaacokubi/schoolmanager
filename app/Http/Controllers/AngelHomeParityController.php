<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AngelHomeParityController extends Controller
{
    public function donations()
    {
        $settings = $this->settings();
        return view('pages.donations', compact('settings'));
    }

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

    public function donationsAdmin(Request $request)
    {
        $query = DB::table('school_donations')->orderByDesc('id');
        if ($request->filled('status')) $query->where('status',$request->status);
        $donations = $query->paginate(25)->withQueryString();
        return view('admin.donations', compact('donations'));
    }

    public function updateDonation(Request $request, int $id)
    {
        $data = $request->validate(['status'=>'required|in:pledged,contacted,received,cancelled']);
        DB::table('school_donations')->where('id',$id)->update($data+['processed_by'=>$request->user()->id,'updated_at'=>now()]);
        return back()->with('success','Donation status updated.');
    }

    public function community(string $type)
    {
        abort_unless(in_array($type, ['teachers','pupils','sponsors'], true), 404);
        $settings = $this->settings();
        $data = match ($type) {
            'teachers' => DB::table('teachers')->whereNull('archived_at')->orderBy('name')->get(),
            'pupils' => DB::table('students')->whereNull('archived_at')->orderBy('name')->limit(100)->get(),
            default => DB::table('parents')->orderBy('name')->limit(100)->get(),
        };
        return view('pages.community', compact('settings','type','data'));
    }

    public function support()
    {
        $settings = $this->settings();
        return view('pages.support', compact('settings'));
    }

    public function featureAction(Request $request, int $id, string $action)
    {
        $record = DB::table('school_feature_records')->where('id',$id)->whereNull('archived_at')->first();
        abort_unless($record, 404);
        $role = strtolower((string)($request->user()->role ?? ''));
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