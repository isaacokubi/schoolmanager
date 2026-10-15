<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
/**
 * Controller for the password reset workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * services, and returns views or HTTP responses. The method comments explain
 * the purpose of each action and its role in the workflow.
 */

class PasswordResetController extends Controller
{
    /**
     * Prepare and display the page associated with this action.
     */
    public function showLinkRequestForm()
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('auth.forgot-password');
    }

    /**
     * Perform the required notification or email step using the configured application service.
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Reject malformed addresses before asking Laravel to send a reset link.
        $request->validate([
            'email' => 'required|email|max:150',
        ]);

        // Match the lowercase/trimmed format used elsewhere in account authentication.
        $email = strtolower(trim($request->input('email')));

        // The sender is controlled by MAIL_FROM_ADDRESS/Brevo. It must never
        // be changed to the submitted recipient email, because doing so can
        // create a sender mismatch and break transactional delivery.
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

    /**
     * Prepare and display the page associated with this action.
     */
    public function showResetForm(Request $request, $token)
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('auth.reset-password', [
            'token' => $token,
            'email' => strtolower(trim($request->query('email', ''))),
        ]);
    }

    /**
     * Verify the reset request and update the password only when the reset token and validation rules are accepted.
     */
    public function reset(Request $request)
    {
        // Validate the emailed token and require the new password fields to match.
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
                // Hash the password before storing it and rotate the remembered-session token.
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            // Redirect the browser to the next route and attach a one-time status message if supplied.
            return redirect()
                ->route('login')
                ->with('status', __($status));
        }

        return back()
            ->withErrors(['email' => __($status)])
            ->withInput(['email' => $email]);
    }
}
