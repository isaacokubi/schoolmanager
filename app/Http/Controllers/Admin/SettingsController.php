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
        ]);

        foreach ($data as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        return back()->with('success', 'School settings saved successfully.');
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
