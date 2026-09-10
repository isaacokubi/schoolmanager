<?php

namespace Tests\Unit;

use Tests\TestCase;

class ApplicationConfigurationTest extends TestCase
{
    public function test_application_uses_the_kenyan_timezone(): void
    {
        $this->assertSame('Africa/Nairobi', config('app.timezone'));
    }

    public function test_database_cache_store_is_configured(): void
    {
        $this->assertSame('database', config('cache.stores.database.driver'));
    }
}
