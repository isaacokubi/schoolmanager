<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DemoSchoolMediaSeeder extends Seeder
{
    /**
     * Download a small set of clearly-labelled demo school media for local/staging previews.
     * This seeder is intentionally separate from DatabaseSeeder so production data is never
     * populated with demo media unless an operator explicitly requests it.
     */
    public function run(): void
    {
        $items = [
            [
                'title' => 'Bright classroom learning',
                'caption' => 'Sample classroom photograph for the school website gallery.',
                'type' => 'image',
                'url' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1600&q=85',
                'extension' => 'jpg',
                'mime_type' => 'image/jpeg',
            ],
            [
                'title' => 'Learners collaborating',
                'caption' => 'Sample learner collaboration photograph for the school website gallery.',
                'type' => 'image',
                'url' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=1600&q=85',
                'extension' => 'jpg',
                'mime_type' => 'image/jpeg',
            ],
            [
                'title' => 'School library & reading',
                'caption' => 'Sample reading and library photograph for the school website gallery.',
                'type' => 'image',
                'url' => 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?auto=format&fit=crop&w=1600&q=85',
                'extension' => 'jpg',
                'mime_type' => 'image/jpeg',
            ],
            [
                'title' => 'School sports day',
                'caption' => 'Sample school sports video for demonstrating the website video gallery. Source: U.S. Navy / DVIDS public-domain footage.',
                'type' => 'video',
                'url' => 'https://d34w7g4gy10iej.cloudfront.net/video/2605/DOD_111735626/DOD_111735626.mp4',
                'extension' => 'mp4',
                'mime_type' => 'video/mp4',
            ],
        ];

        $disk = Storage::disk('public');
        $sortOrder = (int) (DB::table('school_media')->max('sort_order') ?? 0);

        foreach ($items as $item) {
            if (DB::table('school_media')->where('title', $item['title'])->exists()) {
                continue;
            }

            try {
                $response = Http::timeout(120)
                    ->retry(2, 1000)
                    ->withHeaders(['User-Agent' => 'SchoolManager-DemoMediaSeeder/1.0'])
                    ->get($item['url']);
            } catch (\Throwable $e) {
                if ($this->command) {
                    $this->command->warn(
                        "Skipped {$item['title']}: download failed - {$e->getMessage()}"
                    );
                }

                continue;
            }

            if (!$response->successful()) {
                if ($this->command) { $this->command->warn("Skipped {$item['title']}: download returned HTTP {$response->status()}."); }
                continue;
            }

            $body = $response->body();
            if ($body === '') {
                if ($this->command) { $this->command->warn("Skipped {$item['title']}: downloaded file was empty."); }
                continue;
            }

            $path = 'school-media/demo-' . now()->format('YmdHis') . '-' . substr(sha1($item['title']), 0, 10) . '.' . $item['extension'];
            $disk->put($path, $body);

            $sortOrder++;

            DB::table('school_media')->insert([
                'title' => $item['title'],
                'caption' => $item['caption'],
                'type' => $item['type'],
                'path' => $path,
                'mime_type' => $item['mime_type'],
                'file_size' => strlen($body),
                'sort_order' => $sortOrder,
                'published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($this->command) { $this->command->info("Added demo {$item['type']}: {$item['title']}"); }
        }
    }
}
