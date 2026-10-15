<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display the email settings for the currently signed-in user.
     * The view receives the authenticated account, not a user ID supplied by the browser.
     */
    public function edit(Request $request)
    {
        return view('account.email', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the account email and synchronize the linked legacy profile where applicable.
     * Requiring the current password helps protect account recovery from unattended sessions.
     */
    public function updateEmail(Request $request)
    {
        // Use the authenticated account so a request cannot change another user's email.
        $user = $request->user();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'string'],
        ]);

        // Validate the current password explicitly before performing any email change.
        if (!Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.'])->withInput();
        }

        // Normalize email addresses consistently for login and password-recovery lookup.
        $email = strtolower(trim($data['email']));
        $oldEmail = strtolower(trim((string) $user->email));

        // Keep the account and its related teacher/parent profile updates atomic.
        // If a database update fails, Laravel rolls back the entire transaction.
        DB::transaction(function () use ($user, $email, $oldEmail) {
            // Save the canonical login email on the authenticated user record first.
            $user->forceFill(['email' => $email])->save();

            // Portal profiles can link a parent or teacher by admission/employee number.
            $profile = DB::table('portal_profiles')->where('user_id', $user->id)->first();

            // Keep the teacher directory's email aligned with the account when this is a teacher.
            if (in_array($user->role, ['teacher'], true)) {
                $query = DB::table('teachers')->whereNull('archived_at');
                if ($profile && !empty($profile->admission_number)) {
                    $query->where('employee_number', $profile->admission_number);
                } else {
                    $query->where('email', $oldEmail);
                }
                $query->update(['email' => $email, 'updated_at' => now()]);
            }

            // Parent and sponsor accounts may be linked to a parent row through a student profile.
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

        // Confirm that the same address will be used for subsequent sign-in and reset requests.
        return back()->with('success', 'Your email address has been updated. It is now the email used for login and password recovery.');
    }
}
