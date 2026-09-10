<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $teacher = null;
        if ($user->role === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();
        }

        return view('signatures.index', compact('user', 'teacher'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'signature' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($user->role === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();
            abort_unless($teacher, 403, 'Your teacher record is not linked to this account.');

            if ($teacher->signature_path) {
                Storage::disk('public')->delete($teacher->signature_path);
            }
            $path = $request->file('signature')->store('signatures/teachers', 'public');
            DB::table('teachers')->where('id', $teacher->id)->update(['signature_path' => $path, 'updated_at' => now()]);

            return back()->with('success', 'Your teacher signature has been uploaded successfully. It will appear automatically on CBC report cards where you are the class teacher.');
        }

        abort_unless(in_array($user->role, ['admin', 'manager'], true), 403);
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }
        $path = $request->file('signature')->store('signatures/institution', 'public');
        DB::table('users')->where('id', $user->id)->update(['signature_path' => $path, 'updated_at' => now()]);

        return back()->with('success', 'Your institution signature has been uploaded successfully. It will appear automatically when you are designated as Head of Institution.');
    }

    public function remove(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();
            abort_unless($teacher, 403);
            if ($teacher->signature_path) Storage::disk('public')->delete($teacher->signature_path);
            DB::table('teachers')->where('id', $teacher->id)->update(['signature_path' => null, 'updated_at' => now()]);
        } elseif (in_array($user->role, ['admin', 'manager'], true)) {
            if ($user->signature_path) Storage::disk('public')->delete($user->signature_path);
            DB::table('users')->where('id', $user->id)->update(['signature_path' => null, 'updated_at' => now()]);
        } else {
            abort(403);
        }

        return back()->with('success', 'Signature removed successfully.');
    }
}
