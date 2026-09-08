<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $profile = DB::table('portal_profiles')->where('user_id', $user->id)->first();
        $students = collect();
        $teacher = null;
        $subjects = collect();
        $teacherStudentCount = 0;

        if ($profile && $profile->portal_type === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();
            if ($teacher) {
                $subjects = DB::table('subjects')->where('teacher_id', $teacher->id)->orderBy('name')->get();
                $teacherStudentCount = DB::table('students')->count();
            }
        } elseif ($profile && $profile->admission_number) {
            $students = DB::table('students')->where('admission_number', $profile->admission_number)->get();
        } elseif ($profile && in_array($profile->portal_type, ['parent', 'sponsor'], true)) {
            $needle = $user->name;
            $students = DB::table('students')->where('parent_name', $needle)->get();
        }

        return view('portal.dashboard', compact('user', 'profile', 'students', 'teacher', 'subjects', 'teacherStudentCount'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
