<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    private function settings()
    {
        $defaults = [
            'school_name' => 'School Manager',
            'school_phone' => '',
            'school_email' => '',
            'school_address' => '',
            'academic_year' => date('Y'),
            'academic_term' => 'Term 1',
            'currency' => 'KES',
            'timezone' => 'Africa/Nairobi',
            'mission' => 'To provide a safe, inclusive and inspiring learning environment where every learner can develop academically, socially and creatively.',
            'vision' => 'To nurture responsible, confident and capable young people prepared to contribute positively to society.',
            'values' => 'Integrity, respect, excellence, responsibility, teamwork and lifelong learning.',
        ];
        try {
            foreach (DB::table('settings')->get() as $setting) {
                $defaults[$setting->key] = $setting->value;
            }
        } catch (\Throwable $e) {
            // Keep public pages available if settings have not been migrated yet.
        }
        return $defaults;
    }

    public function home()
    {
        return view('home', [
            'settings' => $this->settings(),
            'announcements' => DB::table('announcements')->where('published', true)->orderByDesc('published_at')->orderByDesc('id')->limit(3)->get(),
            'events' => DB::table('events')->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date')->limit(4)->get(),
            'classes' => DB::table('school_classes')->orderBy('academic_year', 'desc')->orderBy('name')->limit(8)->get(),
        ]);
    }

    public function about()
    {
        $settings = $this->settings();
        return view('pages.about', [
            'settings' => $settings,
            'teachers' => DB::table('teachers')->orderBy('name')->limit(8)->get(),
        ]);
    }

    public function academics()
    {
        return view('pages.academics', [
            'settings' => $this->settings(),
            'classes' => DB::table('school_classes')->orderBy('academic_year', 'desc')->orderBy('name')->get(),
            'subjects' => DB::table('subjects')->leftJoin('teachers', 'teachers.id', '=', 'subjects.teacher_id')->select('subjects.*', 'teachers.name as teacher_name')->orderBy('subjects.name')->get(),
        ]);
    }

    public function admissions()
    {
        return view('pages.admissions', [
            'settings' => $this->settings(),
            'classes' => DB::table('school_classes')->orderBy('academic_year', 'desc')->orderBy('name')->get(),
        ]);
    }

    public function contact()
    {
        return view('pages.contact', [
            'settings' => $this->settings(),
            'events' => DB::table('events')->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date')->limit(6)->get(),
        ]);
    }
}
