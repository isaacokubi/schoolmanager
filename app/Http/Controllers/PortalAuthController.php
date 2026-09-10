<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PortalAuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $portalType = $request->input('portal_type');
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'phone' => 'nullable|string|max:30',
            'portal_type' => 'required|in:pupil,parent,sponsor,teacher',
            'admission_number' => 'nullable|string|max:100',
            'employee_number' => 'nullable|string|max:50',
            'relationship' => 'nullable|string|max:50',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);

        if (in_array($data['portal_type'], ['pupil', 'parent', 'sponsor'], true) && empty($data['admission_number'])) {
            return back()->withErrors(['admission_number' => 'Please provide the pupil admission number linked to this account.'])->withInput();
        }

        if ($data['portal_type'] === 'teacher' && empty($data['employee_number'])) {
            return back()->withErrors(['employee_number' => 'Teachers must provide their employee number.'])->withInput();
        }

        $student = null;
        if (!empty($data['admission_number'])) {
            $student = DB::table('students')->where('admission_number', $data['admission_number'])->first();
            if (!$student) {
                return back()->withErrors(['admission_number' => 'The admission number could not be found. Please contact the school.'])->withInput();
            }
        }

        if ($data['portal_type'] === 'pupil') {
            if (!$student || strcasecmp(trim($student->name), trim($data['name'])) !== 0) {
                return back()->withErrors(['name' => 'The name does not match the learner record. Please contact the school if your details need updating.'])->withInput();
            }
        }

        if (in_array($data['portal_type'], ['parent', 'sponsor'], true)) {
            $submittedPhone = preg_replace('/\D+/', '', (string) ($data['phone'] ?? ''));
            $studentPhone = preg_replace('/\D+/', '', (string) ($student->parent_phone ?? ''));
            $parentMatches = false;

            if ($student && $student->parent_id) {
                $parent = DB::table('parents')->where('id', $student->parent_id)->first();
                $parentEmailMatches = $parent && $parent->email && strcasecmp($parent->email, $data['email']) === 0;
                $parentPhoneMatches = $parent && $submittedPhone !== '' && preg_replace('/\D+/', '', (string) $parent->phone) === $submittedPhone;
                $parentMatches = $parentEmailMatches || $parentPhoneMatches;
            }

            if (!$parentMatches && ($submittedPhone === '' || $studentPhone === '' || $submittedPhone !== $studentPhone)) {
                return back()->withErrors(['admission_number' => 'We could not verify your relationship to this learner. Use the parent/guardian phone or email already held by the school.'])->withInput();
            }
        }

        $teacher = null;
        if ($data['portal_type'] === 'teacher') {
            $teacher = DB::table('teachers')->where('employee_number', $data['employee_number'])->first();
            if (!$teacher) {
                return back()->withErrors(['employee_number' => 'The employee number could not be found. Please contact the school administrator.'])->withInput();
            }
            if ($teacher->email && strcasecmp($teacher->email, $data['email']) !== 0) {
                return back()->withErrors(['email' => 'The email does not match the teacher record. Please use the school email on file.'])->withInput();
            }
        }

        $userId = DB::transaction(function () use ($data, $teacher) {
            $userId = DB::table('users')->insertGetId([
                'name' => $teacher ? $teacher->name : $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['portal_type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('portal_profiles')->insert([
                'user_id' => $userId,
                'portal_type' => $data['portal_type'],
                'phone' => $data['phone'] ?? ($teacher->phone ?? null),
                'relationship' => $data['relationship'] ?? null,
                'admission_number' => $data['admission_number'] ?? null,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $userId;
        });

        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }
}
