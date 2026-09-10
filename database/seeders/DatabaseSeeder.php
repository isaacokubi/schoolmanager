<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call([
            DemoSchoolSeeder::class,
            PortalDemoUsersSeeder::class,
            SampleSignatureSeeder::class,
        ]);
    }
}
