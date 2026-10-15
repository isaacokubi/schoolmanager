<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
/**
 * Defines the 2026_09_18_030000_normalize_user_email_identities routine.
 * This file contains database migration or development-seeding logic used by the application.
 */

class NormalizeUserEmailIdentities extends Migration
{
    /**
     * Apply this database migration to create or update the schema.
     */
    public function up()
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'email')) {
            return;
        }

        // Start a query against the named table; subsequent calls specify the filter or operation.
        DB::table('users')
            ->select('id', 'email')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $email = strtolower(trim((string) $user->email));

                    if ($email !== (string) $user->email) {
                        // Start a query against the named table; subsequent calls specify the filter or operation.
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'email' => $email,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    /**
     * Reverse the schema changes introduced by this migration.
     */
    public function down()
    {
        // Email canonicalization is intentionally not reversed.
    }
}
