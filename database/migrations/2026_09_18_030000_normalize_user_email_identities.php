<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class NormalizeUserEmailIdentities extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'email')) {
            return;
        }

        DB::table('users')
            ->select('id', 'email')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $email = strtolower(trim((string) $user->email));

                    if ($email !== (string) $user->email) {
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

    public function down()
    {
        // Email canonicalization is intentionally not reversed.
    }
}
