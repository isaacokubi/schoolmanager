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
        $emailRules = ['required', 'email', 'max:150'];
        if ($portalType !== 'teacher') {
            $emailRules[] = Rule::unique('users', 'email');
        }

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => $emailRules,
            'phone' => 'nullable|string|max:30',
            'portal_type' => 'required|in:pupil,parent,sponsor,teacher',
            'admission_number' => 'nullable|string|max:100',
            'employee_number' => 'nullable|string|max:50',
            'relationship' => 'nullable|string|max:50',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);

        if ($data['portal_type'] === 'pupil' && empty($data['admission_number'])) {
            return back()->withErrors(['admission_number' => 'Pupils must provide their admission number.'])->withInput();
        }

        if (in_array($data['portal_type'], ['parent', 'sponsor'], true) && empty($data['admission_number'])) {
            return back()->withErrors(['admission_number' => 'Please provide the pupil admission number linked to this account.'])->withInput();
        }

        if ($data['portal_type'] === 'teacher' && empty($data['employee_number'])) {
            return back()->withErrors(['employee_number' => 'Teachers must provide their employee number.'])->withInput();
        }

        if (!empty($data['admission_number']) && !DB::table('students')->where('admission_number', $data['admission_number'])->exists()) {
            return back()->withErrors(['admission_number' => 'The admission number could not be found. Please contact the school.'])->withInput();
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
            if (DB::table('users')->where('email', $data['email'])->where('role', 'teacher')->exists()) {
                $userId = DB::table('users')->where('email', $data['email'])->where('role', 'teacher')->value('id');
                DB::table('users')->where('id', $userId)->update([
                    'name' => $teacher->name,
                    'password' => Hash::make($data['password']),
                    'updated_at' => now(),
                ]);
                DB::table('portal_profiles')->updateOrInsert(
                    ['user_id' => $userId],
                    ['portal_type' => 'teacher', 'phone' => $data['phone'] ?: $teacher->phone, 'relationship' => null, 'admission_number' => null, 'active' => true, 'created_at' => now(), 'updated_at' => now()]
                );
                Auth::loginUsingId($userId);
                $request->session()->regenerate();
                return redirect()->route('portal.dashboard');
            }
        }

        $userId = DB::table('users')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['portal_type'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('portal_profiles')->insert([
            'user_id' => $userId,
            'portal_type' => $data['portal_type'],
            'phone' => $data['phone'] ?? null,
            'relationship' => $data['relationship'] ?? null,
            'admission_number' => $data['admission_number'] ?? null,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard');
    }
}
