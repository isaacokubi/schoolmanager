<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email|max:150',
            'password' => 'required|string|min:8|max:255',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The email or password is incorrect.'])->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (in_array($user->role, ['admin', 'manager'], true)) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if (in_array($user->role, ['pupil', 'parent', 'sponsor', 'teacher'], true)) {
            return redirect()->intended(route('portal.dashboard'));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return back()->withErrors(['email' => 'This account does not have an active portal role.'])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
