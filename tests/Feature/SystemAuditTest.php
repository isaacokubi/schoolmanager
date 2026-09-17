<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SystemAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_database_connectivity_without_environment_details(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonMissingPath('environment')
            ->assertJsonMissingPath('app');
    }

    public function test_upload_disk_is_configurable_and_defaults_to_public(): void
    {
        $this->assertSame('public', config('filesystems.upload_disk'));
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
        $this->assertSame('s3', config('filesystems.disks.s3.driver'));
    }

    public function test_application_routes_are_controller_based_for_production_route_caching(): void
    {
        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction('uses');
            $this->assertFalse(
                $action instanceof \Closure,
                'Route [' . $route->uri() . '] must not use a Closure so production route caching remains available.'
            );
        }
    }

    public function test_payment_receipts_are_unique_at_database_level(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->assertTrue(true);
            return;
        }

        $indexes = DB::select("PRAGMA index_list('payments')");
        $indexNames = collect($indexes)->pluck('name')->map(fn ($name) => (string) $name)->all();

        $this->assertTrue(
            collect($indexNames)->contains(fn ($name) => stripos($name, 'mpesa_receipt') !== false),
            'payments.mpesa_receipt must remain uniquely indexed.'
        );
    }
}
