<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    public function test_login_and_email_password_recovery_pages_are_publicly_accessible(): void
    {
        // Password recovery remains available even though public registration is disabled.
        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/example-token')->assertOk();
    }
}
