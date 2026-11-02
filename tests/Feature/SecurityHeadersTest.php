<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_sets_security_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertHeader('x-frame-options', 'DENY')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('referrer-policy', 'strict-origin-when-cross-origin');

        $csp = $response->headers->get('content-security-policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }
}
