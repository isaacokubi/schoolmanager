<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
/**
 * Defines the database seeder routine.
 * This file contains database migration or development-seeding logic used by the application.
 */

class DatabaseSeeder extends Seeder
{
    /**
     * Execute the seeder's data-preparation task and create the records needed by the configured demo or initial environment.
     */
    public function run()
    {
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        // Run another seeder so its related demo or initial data is created as part of this setup.
        $this->call([
            DemoSchoolSeeder::class,
            AssignDemoClassTeachersSeeder::class,
            PortalDemoUsersSeeder::class,
            SampleSignatureSeeder::class,
        ]);
    }
}
