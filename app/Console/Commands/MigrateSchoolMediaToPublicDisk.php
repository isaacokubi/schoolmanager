<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateSchoolMediaToPublicDisk extends Command
{
    protected $signature = 'schoolmedia:migrate-to-public {--delete-source : Delete local source files after successful upload}';

    protected $description = 'Copy existing school media from storage/app/public to the configured public disk and verify database records.';

    public function handle()
    {
        $source = Storage::disk('local');
        $target = Storage::disk('public');
        $records = DB::table('school_media')
            ->whereNotNull('path')
            ->orderBy('id')
            ->get(['id', 'path', 'mime_type']);

        $this->info('Source: local storage/app/public');
        $this->info('Target: public disk (' . config('filesystems.disks.public.driver') . ')');

        $copied = 0;
        $alreadyPresent = 0;
        $missing = 0;
        $failed = 0;

        foreach ($records as $record) {
            $path = ltrim((string) $record->path, '/');
            $sourcePath = 'public/' . $path;

            try {
                if (!$source->exists($sourcePath)) {
                    if ($target->exists($path)) {
                        $alreadyPresent++;
                        $this->line("OK #{$record->id}: target already contains {$path}");
                    } else {
                        $missing++;
                        $this->warn("MISSING #{$record->id}: {$sourcePath}");
                    }
                    continue;
                }

                if ($target->exists($path)) {
                    $alreadyPresent++;
                    $this->line("OK #{$record->id}: target already contains {$path}");
                    continue;
                }

                $stream = $source->readStream($sourcePath);
                if ($stream === false) {
                    throw new \RuntimeException('Unable to open source stream.');
                }

                $written = $target->put($path, $stream, [
                    'visibility' => config('filesystems.disks.public.visibility', 'public'),
                    'ContentType' => $record->mime_type ?: $source->mimeType($sourcePath),
                ]);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (!$written || !$target->exists($path)) {
                    throw new \RuntimeException('Target write could not be verified.');
                }

                $copied++;
                $this->info("COPIED #{$record->id}: {$path}");

                if ($this->option('delete-source')) {
                    $source->delete($sourcePath);
                }
            } catch (Throwable $e) {
                $failed++;
                $this->error("FAILED #{$record->id} {$path}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->table(['Result', 'Count'], [
            ['Copied', $copied],
            ['Already present', $alreadyPresent],
            ['Missing from both disks', $missing],
            ['Failed', $failed],
            ['Database records checked', $records->count()],
        ]);

        return ($failed > 0 || $missing > 0) ? self::FAILURE : self::SUCCESS;
    }
}
