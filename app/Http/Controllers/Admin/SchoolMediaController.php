<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SchoolMediaController extends Controller
{
    public function index()
    {
        $media = DB::table('school_media')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(24);

        return view('admin.media.index', compact('media'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['image', 'video'])],
            'media' => [
                'required',
                'file',
                'max:51200',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('type');

                    if ($type === 'image' && !str_starts_with((string) $value->getMimeType(), 'image/')) {
                        $fail('Please upload a valid image file.');
                    }

                    if ($type === 'video' && !str_starts_with((string) $value->getMimeType(), 'video/')) {
                        $fail('Please upload a valid video file.');
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

    public function update(Request $request, int $id)
    {
        $media = DB::table('school_media')->where('id', $id)->first();

        abort_unless($media, 404);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

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

    public function destroy(int $id)
    {
        $media = DB::table('school_media')->where('id', $id)->first();

        abort_unless($media, 404);

        if ($media->path && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        DB::table('school_media')->where('id', $id)->delete();

        return back()->with('success', 'Media removed from the school website.');
    }
}
