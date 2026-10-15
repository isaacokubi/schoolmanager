<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
/**
 * Controller for the school media workflow.
 *
 * This class receives HTTP requests, coordinates validation and application
 * logic, and returns responses or views. The method comments below explain
 * the purpose of each action and how it fits into the workflow.
 */

class SchoolMediaController extends Controller
{
    /**
     * Prepare the data needed by the page, apply any supported filters, and return the rendered listing or dashboard.
     * Any request filters are read from the Request object and applied before data is returned.
     */
    public function index()
    {
        $media = DB::table('school_media')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(24);

        // Render the Blade template and pass it the data prepared by this controller.
        return view('admin.media.index', compact('media'));
    }

    /**
     * Handle submitted form data: validate it, prepare any required values, save the record, and return a response to the user.
     * Validate user-controlled input before writing changes to the database.
     */
    public function store(Request $request)
    {
        $videoMaxKb = max(1024, (int) env('VIDEO_UPLOAD_MAX_KB', 4300));

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['image', 'video'])],
            'media' => [
                'required',
                'file',
                'max:51200',
                function ($attribute, $value, $fail) use ($request, $videoMaxKb) {
                    $type = $request->input('type');
                    $mime = (string) $value->getMimeType();

                    if ($type === 'image' && strpos($mime, 'image/') !== 0) {
                        $fail('Please upload a valid image file.');
                    }

                    if ($type === 'video' && strpos($mime, 'video/') !== 0) {
                        $fail('Please upload a valid video file.');
                    }

                    if ($type === 'video' && ((int) $value->getSize() > ($videoMaxKb * 1024))) {
                        $fail(sprintf(
                            'Video files must be %s MB or smaller for this Vercel deployment.',
                            number_format($videoMaxKb / 1000, 1)
                        ));
                    }
                },
            ],
        ]);

        $path = $request->file('media')->store('school-media', 'public');

        DB::table('school_media')->insert([
            'title' => $validated['title'] ?: null,
            'caption' => $validated['caption'] ?: null,
            'type' => $validated['type'],
            'path' => $path,
            'mime_type' => $request->file('media')->getMimeType(),
            'file_size' => $request->file('media')->getSize(),
            'sort_order' => (int) (DB::table('school_media')->max('sort_order') ?? 0) + 1,
            'published' => $request->boolean('published', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'School media uploaded successfully.');
    }

    /**
     * Validate submitted changes, update the matching record, and return a confirmation response.
     * Validate user-controlled input before writing changes to the database.
     */
    public function update(Request $request, int $id)
    {
        $media = DB::table('school_media')->where('id', $id)->first();

        // Stop the request with the specified HTTP error when the required condition is not met.
        abort_unless($media, 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        // Start a query against the named database table; chained calls add conditions or perform the operation.
        DB::table('school_media')
            ->where('id', $id)
            ->update([
                'title' => $validated['title'] ?: null,
                'caption' => $validated['caption'] ?: null,
                'sort_order' => $validated['sort_order'],
                'published' => $request->boolean('published'),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Media details updated.');
    }

    /**
     * Handle removal or archiving of the selected record while following the application's data-retention rules.
     * Check the selected record and follow the existing retention/security behavior.
     */
    public function destroy(int $id)
    {
        $media = DB::table('school_media')->where('id', $id)->first();

        // Stop the request with the specified HTTP error when the required condition is not met.
        abort_unless($media, 404);

        if ($media->path && Storage::disk('public')->exists($media->path)) {
            // Use Laravel's configured storage disk for this file operation.
            Storage::disk('public')->delete($media->path);
        }

        DB::table('school_media')->where('id', $id)->delete();

        return back()->with('success', 'Media removed from the school website.');
    }
}
