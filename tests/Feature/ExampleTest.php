<?php

<<<<<<< HEAD
namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
=======
test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});
>>>>>>> fc2c30cde20eababab8bfcd5d7ffc2d5595d5b6f
