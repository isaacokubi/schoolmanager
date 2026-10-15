<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
/**
 * Controller for the signature workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * services, and returns views or HTTP responses. The method comments explain
 * the purpose of each action and its role in the workflow.
 */

class SignatureController extends Controller
{
    /**
     * Load and display the requested record or page.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $teacher = null;
        if ($user->role === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();
        }

        // Render the Blade template and pass it the data prepared by this controller.
        return view('signatures.index', compact('user', 'teacher'));
    }

    /**
     * Validate and apply the requested changes to the selected record.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        // Validate request input before this workflow uses it or writes changes to the database.
        $request->validate([
            'signature' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($user->role === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();
            // Stop with the specified HTTP error when the required condition is false.
            abort_unless($teacher, 403, 'Your teacher record is not linked to this account.');

            if ($teacher->signature_path) {
                // Use Laravel's configured storage disk for this file operation.
                Storage::disk('public')->delete($teacher->signature_path);
            }
            $path = $request->file('signature')->store('signatures/teachers', 'public');
            DB::table('teachers')->where('id', $teacher->id)->update(['signature_path' => $path, 'updated_at' => now()]);

            return back()->with('success', 'Your teacher signature has been uploaded successfully. It will appear automatically on CBC report cards where you are the class teacher.');
        }

        // Stop with the specified HTTP error when the required condition is false.
        abort_unless(in_array($user->role, ['admin', 'manager'], true), 403);
        if ($user->signature_path) {
            // Use Laravel's configured storage disk for this file operation.
            Storage::disk('public')->delete($user->signature_path);
        }
        $path = $request->file('signature')->store('signatures/institution', 'public');
        DB::table('users')->where('id', $user->id)->update(['signature_path' => $path, 'updated_at' => now()]);

        return back()->with('success', 'Your institution signature has been uploaded successfully. It will appear automatically when you are designated as Head of Institution.');
    }

    /**
     * Remove or archive the selected item while preserving the application's data-retention rules.
     */
    public function remove(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'teacher') {
            $teacher = DB::table('teachers')->where('email', $user->email)->first();
            // Stop with the specified HTTP error when the required condition is false.
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
