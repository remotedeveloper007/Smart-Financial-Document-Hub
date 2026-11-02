<?php

namespace Tests\Feature;

use App\Jobs\TallyImportFromFileJob;
use App\Models\Integration;
use App\Models\Team;
use App\Services\Integrations\TallyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TallyImportFromFileJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_imports_exact_mapped_values_from_csv_and_sends_idempotency_key(): void
    {
        Storage::fake('local');
        Http::fake(function ($request) {
            $this->assertSame('Bearer team-token', $request->header('Authorization')[0] ?? null);
            $this->assertNotEmpty($request->header('Idempotency-Key')[0] ?? null);
            $this->assertSame([
                'transactions' => [
                    ['date' => '2026-10-01', 'description' => 'Office, supplies', 'amount' => '25.75', 'reference' => 'INV-7'],
                    ['date' => '2026-10-02', 'description' => "Two lines\ncontinued", 'amount' => '10.00', 'reference' => 'INV-8'],
                ],
            ], $request->data());

            return Http::response(['accepted' => true], 202);
        });

        $team = Team::factory()->create();
        $integration = Integration::factory()->create([
            'team_id' => $team->id,
            'provider' => 'tally',
            'credentials' => [
                'url' => 'https://tally.example.test/api',
                'access_token' => 'team-token',
            ],
        ]);
        $sessionId = (string) Str::uuid();
        $filePath = "tenant-{$team->id}/uploads/assembled/{$sessionId}_transactions.csv";
        $contents = "date,description,amount,reference\r\n2026-10-01,\"Office, supplies\",25.75,INV-7\r\n2026-10-02,\"Two lines\ncontinued\",10.00,INV-8\r\n";
        Storage::disk('local')->put($filePath, $contents);
        Storage::disk('local')->put($filePath.'.scan.json', json_encode([
            'path' => $filePath,
            'status' => 'clean',
            'scanner' => 'clamav',
            'scanned_at' => now()->toIso8601String(),
        ]));

        app(TallyImportFromFileJob::class, [
            'integrationId' => $integration->id,
            'teamId' => $team->id,
            'filePath' => $filePath,
        ])->handle(app(TallyService::class), app(\App\Services\Integrations\TallyCsvImportParser::class));

        Http::assertSentCount(1);
    }

    public function test_job_refuses_a_file_from_another_tenant(): void
    {
        $team = Team::factory()->create();
        $integration = Integration::factory()->create(['team_id' => $team->id, 'provider' => 'tally']);
        $job = new TallyImportFromFileJob($integration->id, $team->id, 'tenant-999/uploads/assembled/foreign.csv');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('outside the integration tenant storage path');

        $job->handle(app(TallyService::class), app(\App\Services\Integrations\TallyCsvImportParser::class));
    }

    public function test_job_refuses_a_file_without_a_clean_clamav_result(): void
    {
        Storage::fake('local');
        Http::fake();

        $team = Team::factory()->create();
        $integration = Integration::factory()->create(['team_id' => $team->id, 'provider' => 'tally']);
        $filePath = "tenant-{$team->id}/uploads/assembled/".Str::uuid()."_transactions.csv";
        Storage::disk('local')->put($filePath, "date,description,amount,reference\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tally import file has no malware scan result.');

        (new TallyImportFromFileJob($integration->id, $team->id, $filePath))
            ->handle(app(TallyService::class), app(\App\Services\Integrations\TallyCsvImportParser::class));
    }
}
