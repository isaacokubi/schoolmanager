<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
/**
 * Controller for the public workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * services, and returns views or HTTP responses. The method comments explain
 * the purpose of each action and its role in the workflow.
 */

class PublicController extends Controller
{
    /**
     * Helper method for PublicController.php; it performs the settings step and returns or updates data used by its caller.
     */
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

    /**
     * Helper method for PublicController.php; it performs the home step and returns or updates data used by its caller.
     */
    public function home()
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('home', [
            'settings' => $this->settings(),
            'announcements' => DB::table('announcements')->where('published', true)->orderByDesc('published_at')->orderByDesc('id')->limit(3)->get(),
            'events' => DB::table('events')->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date')->limit(4)->get(),
            'classes' => DB::table('school_classes')->orderBy('academic_year', 'desc')->orderBy('name')->limit(8)->get(),
            'schoolMedia' => DB::table('school_media')
                ->where('published', true)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit(12)
                ->get(),
        ]);
    }

    /**
     * Helper method for PublicController.php; it performs the about step and returns or updates data used by its caller.
     */
    public function about()
    {
        $settings = $this->settings();
        // Render the Blade template and pass it the data prepared by this controller.
        return view('pages.about', [
            'settings' => $settings,
            'teachers' => DB::table('teachers')->orderBy('name')->limit(8)->get(),
        ]);
    }

    /**
     * Helper method for PublicController.php; it performs the academics step and returns or updates data used by its caller.
     */
    public function academics()
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('pages.academics', [
            'settings' => $this->settings(),
            'classes' => DB::table('school_classes')->orderBy('academic_year', 'desc')->orderBy('name')->get(),
            'subjects' => DB::table('subjects')->leftJoin('teachers', 'teachers.id', '=', 'subjects.teacher_id')->select('subjects.*', 'teachers.name as teacher_name')->orderBy('subjects.name')->get(),
        ]);
    }

    /**
     * Helper method for PublicController.php; it performs the admissions step and returns or updates data used by its caller.
     */
    public function admissions()
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('pages.admissions', [
            'settings' => $this->settings(),
            'classes' => DB::table('school_classes')->orderBy('academic_year', 'desc')->orderBy('name')->get(),
        ]);
    }

    /**
     * Helper method for PublicController.php; it performs the support step and returns or updates data used by its caller.
     */
    public function support()
    {
        return app(AngelHomeParityController::class)->support();
    }

    /**
     * Helper method for PublicController.php; it performs the contact step and returns or updates data used by its caller.
     */
    public function contact()
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('pages.contact', [
            'settings' => $this->settings(),
            'events' => DB::table('events')->whereDate('event_date', '>=', now()->toDateString())->orderBy('event_date')->limit(6)->get(),
        ]);
    }
}
