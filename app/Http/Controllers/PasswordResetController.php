<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:150',
        ]);

        $email = strtolower(trim($request->input('email')));

        // Use the same configured school/recovery address for the outgoing
        // password-reset email. Laravel's password broker still resolves the
        // actual user by users.email.
        $schoolEmail = DB::table('settings')->where('key', 'school_email')->value('value');
        if ($schoolEmail && strtolower(trim($schoolEmail)) === $email) {
            config(['mail.from.address' => $email]);
        }

        $status = Password::broker('users')->sendResetLink([
            'email' => $email,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()
            ->withErrors(['email' => __($status)])
            ->withInput(['email' => $email]);
    }

    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => strtolower(trim($request->query('email', ''))),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|max:255|confirmed',
        ]);

        $email = strtolower(trim($request->input('email')));

        // This resets the password on the existing users row identified by
        // the account's current email. There is no separate "reset password"
        // credential: the new password becomes the password used by login.
        $status = Password::broker('users')->reset(
            [
                'email' => $email,
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', __($status));
        }

        return back()
            ->withErrors(['email' => __($status)])
            ->withInput(['email' => $email]);
    }
}
