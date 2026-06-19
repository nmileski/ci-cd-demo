<?php

namespace Tests\Feature;

use Tests\TestCase;

class HelloTest extends TestCase
{
    public function test_hello_route_returns_greeting(): void
    {
        $response = $this->get('/hello');

        $response->assertStatus(200);
        $response->assertSee('Hello, CI/CD!');
    }
}