<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;

class TallyService
{
    /**
     * Test connection to a Tally-like API using provided credentials.
     * Supports either `token` (Bearer) or `security_code` header and a custom `url`.
     */
    public function testConnection(array $credentials): bool
    {
        $url = $credentials['url'] ?? config('services.tally.url', 'https://api.example.com');

        $request = Http::withHeaders([
            'Accept' => 'application/json',
        ]);

        $token = $credentials['token'] ?? $credentials['access_token'] ?? null;
        if (! empty($token)) {
            $request = $request->withToken($token);
        }

        if (! empty($credentials['security_code'])) {
            $request = $request->withHeaders(['X-Security-Code' => $credentials['security_code']]);
        }

        // Use a lightweight endpoint to validate credentials if available.
        $endpoint = rtrim($url, '/') . '/ping';

        try {
            $response = $request->get($endpoint);

            if ($response->successful()) {
                return true;
            }
        } catch (\Throwable) {
            // swallow - return false below
        }

        return false;
    }

    public function listCompanies(array $credentials): array
    {
        $url = $credentials['url'] ?? config('services.tally.url', 'https://api.example.com');

        $request = Http::withHeaders(['Accept' => 'application/json']);

        $token = $credentials['token'] ?? $credentials['access_token'] ?? null;
        if (! empty($token)) {
            $request = $request->withToken($token);
        }

        if (! empty($credentials['security_code'])) {
            $request = $request->withHeaders(['X-Security-Code' => $credentials['security_code']]);
        }

        try {
            $response = $request->get(rtrim($url, '/') . '/companies');

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
        $url = $credentials['url'] ?? config('services.tally.url', 'https://api.example.com');

        $request = Http::withHeaders(['Accept' => 'application/json']);

        $token = $credentials['token'] ?? $credentials['access_token'] ?? null;
        if (! empty($token)) {
            $request = $request->withToken($token);
        }

        if (! empty($credentials['security_code'])) {
            $request = $request->withHeaders(['X-Security-Code' => $credentials['security_code']]);
        }

        $endpoint = rtrim($url, '/') . '/ledgers';
        if ($companyId) {
            $endpoint .= '?company_id=' . urlencode($companyId);
        }

        try {
            $response = $request->get($endpoint);

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
        $url = $credentials['url'] ?? config('services.tally.url', 'https://api.example.com');

        $request = Http::withHeaders(['Accept' => 'application/json']);

        $token = $credentials['token'] ?? $credentials['access_token'] ?? null;
        if (! empty($token)) {
            $request = $request->withToken($token);
        }

        if (! empty($credentials['security_code'])) {
            $request = $request->withHeaders(['X-Security-Code' => $credentials['security_code']]);
        }

        $endpoint = rtrim($url, '/') . '/transactions';
        if (! empty($filters)) {
            $endpoint .= '?' . http_build_query($filters);
        }

        try {
            $response = $request->get($endpoint);

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable) {
            // ignore
        }

        return [];
    }

    public function importTransactions(array $credentials, array $transactions): bool
    {
        $url = $credentials['url'] ?? config('services.tally.url', 'https://api.example.com');

        $request = Http::withHeaders(['Accept' => 'application/json']);

        $token = $credentials['token'] ?? $credentials['access_token'] ?? null;
        if (! empty($token)) {
            $request = $request->withToken($token);
        }

        if (! empty($credentials['security_code'])) {
            $request = $request->withHeaders(['X-Security-Code' => $credentials['security_code']]);
        }

        try {
            $response = $request->post(rtrim($url, '/') . '/transactions/import', ['transactions' => $transactions]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function exportTransactions(array $credentials, array $filters = null): array
    {
        $url = $credentials['url'] ?? config('services.tally.url', 'https://api.example.com');

        $request = Http::withHeaders(['Accept' => 'application/json']);

        $token = $credentials['token'] ?? $credentials['access_token'] ?? null;
        if (! empty($token)) {
            $request = $request->withToken($token);
        }

        if (! empty($credentials['security_code'])) {
            $request = $request->withHeaders(['X-Security-Code' => $credentials['security_code']]);
        }

        $endpoint = rtrim($url, '/') . '/transactions/export';
        if (! empty($filters)) {
            $endpoint .= '?' . http_build_query($filters);
        }

        try {
            $response = $request->get($endpoint);

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable) {
            // ignore
        }

        return [];
    }
}
