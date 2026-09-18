<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.email', [
            'user' => $request->user(),
        ]);
    }

    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'string'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.'])->withInput();
        }

        $email = strtolower(trim($data['email']));
        $oldEmail = strtolower(trim((string) $user->email));

        DB::transaction(function () use ($user, $email, $oldEmail) {
            $user->forceFill(['email' => $email])->save();

            $profile = DB::table('portal_profiles')->where('user_id', $user->id)->first();

            if (in_array($user->role, ['teacher'], true)) {
                $query = DB::table('teachers')->whereNull('archived_at');
                if ($profile && !empty($profile->admission_number)) {
                    $query->where('employee_number', $profile->admission_number);
                } else {
                    $query->where('email', $oldEmail);
                }
                $query->update(['email' => $email, 'updated_at' => now()]);
            }

            if (in_array($user->role, ['parent', 'sponsor'], true)) {
                $parentQuery = DB::table('parents')->whereNull('archived_at');
                if ($profile && !empty($profile->admission_number)) {
                    $student = DB::table('students')
                        ->where('admission_number', $profile->admission_number)
                        ->first(['parent_id']);
                    if ($student && $student->parent_id) {
                        $parentQuery->where('id', $student->parent_id);
                    } else {
                        $parentQuery->where('email', $oldEmail);
                    }
                } else {
                    $parentQuery->where('email', $oldEmail);
                }
                $parentQuery->update(['email' => $email, 'updated_at' => now()]);
            }
        });

        return back()->with('success', 'Your email address has been updated. It is now the email used for login and password recovery.');
    }
}
