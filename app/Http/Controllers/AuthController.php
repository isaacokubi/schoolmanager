<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
/**
 * Controller for the auth workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * logic, and returns responses or views. The method comments below explain
 * the purpose of each action and how it fits into the workflow.
 */

class AuthController extends Controller
{
    // Render the sign-in page; credential checking happens in login().
    public function showLogin()
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('auth.login');
    }

    /**
     * Validate the submitted credentials and establish an authenticated session only when the credentials are accepted.
     */
    public function login(Request $request)
    {
        // Validate submitted credentials before attempting authentication.
        $credentials = $request->validate([
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|max:255',
        ]);

        // The current email on the user account is the login identity.
        // Normalize it so changing capitalization/spacing never creates a
        // different login identity.
        $credentials['email'] = strtolower(trim($credentials['email']));

        // Laravel checks the email/password pair; remember controls persistent sign-in.
        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->withInput($request->only('email'));
        }

        // Rotate the session ID after login to reduce session-fixation risk.
        $request->session()->regenerate();
        $user = $request->user();

        if (in_array($user->role, ['admin', 'manager'], true)) {
            // Redirect the browser to the next page and attach a one-time message when provided.
            return redirect()->intended(route('admin.dashboard'));
        }

        if (in_array($user->role, ['pupil', 'parent', 'sponsor', 'teacher'], true)) {
            // Redirect the browser to the next page and attach a one-time message when provided.
            return redirect()->intended(route('portal.dashboard'));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return back()->withErrors(['email' => 'This account does not have an active portal role.'])->withInput($request->only('email'));
    }

    /**
     * Show the signed-in administrator's email settings.
     */
    public function showChangeEmail()
    {
        // Render the Blade template and pass it the data prepared by this controller.
        return view('admin.account.email');
    }

    /**
     * Change the email used for future sign-ins and password reset messages.
     * The current password is required to prevent an unattended session from
     * silently redirecting account recovery to another address.
     */
    public function changeEmail(Request $request)
    {
        // Only update the currently authenticated account.
        $user = $request->user();

        // Require a unique email, matching confirmation, and the current password.
        $data = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                'confirmed',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => ['required', 'string', 'current_password:web'],
        ], [
            'email.confirmed' => 'The new email address confirmation does not match.',
            'email.unique' => 'That email address is already registered to another account.',
            'current_password.current_password' => 'Your current password is incorrect.',
        ]);

        // Normalize capitalization and whitespace before comparing or saving.
        $newEmail = strtolower(trim($data['email']));

        if (strtolower(trim((string) $user->email)) === $newEmail) {
            return back()->withErrors([
                'email' => 'That is already the email address registered to your account.',
            ])->withInput($request->only('email', 'email_confirmation'));
        }

        // Save the new address on the existing user record for future sign-ins and resets.
        $user->email = $newEmail;
        $user->save();

        // Redirect the browser to the next page and attach a one-time message when provided.
        return redirect()
            ->route('admin.account.email')
            ->with('success', 'Your registered email address has been updated. Use the new address the next time you sign in or request a password reset.');
    }

    // Sign out and invalidate session state before returning to the login page.
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        // Redirect the browser to the next page and attach a one-time message when provided.
        return redirect()->route('login');
    }
}
