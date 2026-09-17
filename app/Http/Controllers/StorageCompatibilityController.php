<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StorageCompatibilityController extends Controller
{
    public function __invoke(string $path)
    {
        $diskName = config('filesystems.upload_disk', 'public');

        // The local/public disk is already served by the web server's storage link.
        if ($diskName === 'public') {
            abort(404);
        }

        $disk = Storage::disk($diskName);
        $path = ltrim($path, '/');

        if ($path === '' || !$disk->exists($path)) {
            abort(404);
        }

        return redirect()->away($disk->url($path));
    }
}
