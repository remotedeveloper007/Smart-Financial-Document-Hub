<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class FaultCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_faults_are_logged_to_fault_logs_table(): void
    {
        $user = User::factory()->create();

        Route::get('/__test/fault', function () {
            throw new \RuntimeException('unit test fault');
        });

        $response = $this->actingAs($user)->get('/__test/fault');

        $response->assertStatus(500);

        $this->assertDatabaseCount('fault_logs', 1);
        $this->assertDatabaseHas('fault_logs', [
            'message' => 'unit test fault',
            'user_id' => $user->id,
        ]);

    }

    public function test_fault_capture_redacts_secret_values(): void
    {
        Route::get('/__test/sensitive-fault', function () {
            throw new \RuntimeException('Provider failed with api_key=secret-value');
        });

        $this->get('/__test/sensitive-fault')->assertStatus(500);

        $this->assertDatabaseHas('fault_logs', [
            'message' => 'Provider failed with api_key=[REDACTED]',
        ]);
    }
}
