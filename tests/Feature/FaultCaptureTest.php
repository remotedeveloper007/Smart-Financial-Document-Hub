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

        $logFile = storage_path('logs/fault_capture_persistent.log');
        if (file_exists($logFile)) {
            @unlink($logFile);
        }

        $response = $this->actingAs($user)->get('/__test/fault');

        $response->assertStatus(500);

        $laravelLog = storage_path('logs/laravel.log');
        $this->assertFileExists($laravelLog);

        $contents = file_get_contents($laravelLog);
        $this->assertStringContainsString('unit test fault', $contents);
    }
}
