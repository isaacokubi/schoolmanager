<?php

// PRESENTATION NOTE: PublicAccessTest.
// This file defines application routes or automated tests. The comments explain
// the purpose of the route/test setup; they do not change request handling or assertions.

namespace Tests\Feature;

use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    /**
     * Verify the application behavior described by this test method; assertions below define the expected result.
     */
    public function test_login_and_email_password_recovery_pages_are_publicly_accessible(): void
    {
        // Password recovery remains available even though public registration is disabled.
        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertOk();
        $this->get('/reset-password/example-token')->assertOk();
    }
}
