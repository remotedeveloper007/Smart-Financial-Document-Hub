<?php

namespace Tests\Feature;

use App\Services\Integrations\TallyService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Tests\TestCase;

class TallyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tally_service_tests_connection_with_token()
    {
        Http::fake(function ($request) {
            $this->assertSame('Bearer secret-token', $request->header('Authorization')[0] ?? null);

            return Http::response(['ok' => true], 200);
        });

        $service = new TallyService();

        $result = $service->testConnection([
            'url' => 'https://tally.example.com',
            'access_token' => 'secret-token',
        ]);

        $this->assertTrue($result);
    }

    public function test_tally_service_tests_connection_with_security_code()
    {
        Http::fake(function ($request) {
            $this->assertSame('sec-abc', $request->header('X-Security-Code')[0] ?? null);

            return Http::response(['ok' => true], 200);
        });

        $service = new TallyService();

        $result = $service->testConnection([
            'url' => 'https://tally.example.com',
            'security_code' => 'sec-abc',
        ]);

        $this->assertTrue($result);
    }

    public function test_list_companies_and_ledgers_and_transactions()
    {
        Http::fake([
            'https://tally.example.com/companies' => Http::response([['id' => 'c1', 'name' => 'Acme']], 200),
            'https://tally.example.com/ledgers*' => Http::response([['id' => 'l1', 'name' => 'Cash']], 200),
            'https://tally.example.com/transactions*' => Http::response([['id' => 't1', 'amount' => 100]], 200),
        ]);

        $service = new TallyService();

        $companies = $service->listCompanies(['url' => 'https://tally.example.com']);
        $this->assertCount(1, $companies);

        $ledgers = $service->listLedgers(['url' => 'https://tally.example.com'], 'c1');
        $this->assertCount(1, $ledgers);

        $tx = $service->listTransactions(['url' => 'https://tally.example.com']);
        $this->assertCount(1, $tx);
    }

    public function test_import_sends_mapped_rows_and_idempotency_key(): void
    {
        Http::fake(function (Request $request) {
            $this->assertSame('Bearer token-123', $request->header('Authorization')[0] ?? null);
            $this->assertSame('stable-import-key', $request->header('Idempotency-Key')[0] ?? null);
            $this->assertSame([
                'transactions' => [
                    ['date' => '2026-10-01', 'description' => 'Office supplies', 'amount' => '25.75'],
                ],
            ], $request->data());

            return Http::response(['accepted' => true], 202);
        });

        $result = app(TallyService::class)->importTransactions(
            ['url' => 'https://tally.example.com', 'access_token' => 'token-123'],
            [['date' => '2026-10-01', 'description' => 'Office supplies', 'amount' => '25.75']],
            'stable-import-key',
        );

        $this->assertTrue($result);
        Http::assertSentCount(1);
    }

    public function test_import_throws_on_remote_failure_so_queue_can_retry(): void
    {
        Http::fake(['https://tally.example.com/transactions/import' => Http::response(['error' => 'busy'], 503)]);

        $this->expectException(RequestException::class);

        app(TallyService::class)->importTransactions(
            ['url' => 'https://tally.example.com'],
            [['reference' => 'R-1']],
            'retry-key',
        );
    }

    public function test_placeholder_or_non_http_urls_are_rejected(): void
    {
        Http::fake();

        foreach (['https://api.example.com', 'file:///tmp/tally'] as $url) {
            $this->assertFalse(app(TallyService::class)->testConnection(['url' => $url]));
        }

        Http::assertNothingSent();
    }
}
