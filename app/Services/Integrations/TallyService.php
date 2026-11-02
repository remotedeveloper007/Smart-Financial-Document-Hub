<?php

namespace App\Services\Integrations;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use UnexpectedValueException;

class TallyService
{
    /**
     * Test connection to a Tally-like API using provided credentials.
     * Supports either `token` (Bearer) or `security_code` header and a custom `url`.
     */
    public function testConnection(array $credentials): bool
    {
        try {
            $response = $this->request($credentials)->get($this->url($credentials).'/ping');
            if ($response->successful()) {
                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    public function listCompanies(array $credentials): array
    {
        try {
            $response = $this->request($credentials)->get($this->url($credentials).'/companies');

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable) {
            // ignore
        }

        return [];
    }

    public function listLedgers(array $credentials, ?string $companyId = null): array
    {
        try {
            $response = $this->request($credentials)->get($this->url($credentials).'/ledgers', array_filter(['company_id' => $companyId]));

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable) {
            // ignore
        }

        return [];
    }

    public function listTransactions(array $credentials, ?array $filters = null): array
    {
        try {
            $response = $this->request($credentials)->get($this->url($credentials).'/transactions', $filters ?? []);

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable) {
            // ignore
        }

        return [];
    }

    public function importTransactions(array $credentials, array $transactions, string $idempotencyKey): bool
    {
        if ($transactions === []) {
            throw new InvalidArgumentException('At least one mapped transaction is required.');
        }

        $response = $this->request($credentials)
            ->withHeader('Idempotency-Key', $idempotencyKey)
            ->post($this->url($credentials).'/transactions/import', ['transactions' => $transactions]);

        if ($response->failed()) {
            $response->throw();
        }

        return true;
    }

    public function exportTransactions(array $credentials, ?array $filters = null): array
    {
        $response = $this->request($credentials)->get($this->url($credentials).'/transactions/export', $filters ?? []);
        $response->throw();
        $data = $response->json();

        if (! is_array($data)) {
            throw new UnexpectedValueException('Tally-compatible export response must be a JSON array or object.');
        }

        return $data;
    }

    private function request(array $credentials): PendingRequest
    {
        $request = Http::acceptJson()->connectTimeout(3)->timeout(15);
        $token = $credentials['token'] ?? $credentials['access_token'] ?? null;

        if (is_string($token) && $token !== '') {
            $request = $request->withToken($token);
        }

        if (! empty($credentials['security_code'])) {
            $request = $request->withHeader('X-Security-Code', (string) $credentials['security_code']);
        }

        return $request;
    }

    private function url(array $credentials): string
    {
        $url = trim((string) ($credentials['url'] ?? config('services.tally.url', '')));
        $parts = parse_url($url);

        if (! is_array($parts)
            || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || strtolower($parts['host']) === 'api.example.com') {
            throw new InvalidArgumentException('Configure a valid Tally-compatible HTTP(S) API URL without embedded credentials.');
        }

        return rtrim($url, '/');
    }
}
