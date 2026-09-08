<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    private $defaults = [
        'school_name' => 'School Manager',
        'school_phone' => '',
        'school_email' => '',
        'school_address' => '',
        'academic_year' => '',
        'academic_term' => '',
        'currency' => 'KES',
        'timezone' => 'Africa/Nairobi',
        'mission' => 'To provide a safe, inclusive and inspiring learning environment where every learner can develop academically, socially and creatively.',
        'vision' => 'To nurture responsible, confident and capable young people prepared to contribute positively to society.',
        'values' => 'Integrity, respect, excellence, responsibility, teamwork and lifelong learning.',
    ];

    public function index()
    {
        $settings = $this->values();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:150',
            'school_phone' => 'nullable|string|max:40',
            'school_email' => 'nullable|email|max:150',
            'school_address' => 'nullable|string|max:500',
            'academic_year' => 'nullable|integer|min:2000|max:2100',
            'academic_term' => 'nullable|string|max:50',
            'currency' => 'required|string|size:3',
            'timezone' => 'required|string|max:100',
            'mission' => 'nullable|string|max:2000',
            'vision' => 'nullable|string|max:2000',
            'values' => 'nullable|string|max:2000',
        ]);

        foreach ($data as $key => $value) {
            $exists = DB::table('settings')->where('key', $key)->exists();
            $payload = ['value' => (string) $value, 'updated_at' => now()];
            if (!$exists) $payload['created_at'] = now();
            DB::table('settings')->updateOrInsert(['key' => $key], $payload);
        }
        return back()->with('success', 'School settings saved successfully. Public pages now use these values.');
    }

    private function values()
    {
        $values = $this->defaults;
        try {
            foreach (DB::table('settings')->get() as $setting) $values[$setting->key] = $setting->value;
        } catch (\Throwable $e) {}
        return $values;
    }
}
