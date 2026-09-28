<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    // home page markets, products aur farmers parhta hai, is liye asli database chahiye
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
