<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    public function test_login_page_is_publicly_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
