<?php

// PRESENTATION NOTE: TestCase.
// This file defines application routes or automated tests. The comments explain
// the purpose of the route/test setup; they do not change request handling or assertions.

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
