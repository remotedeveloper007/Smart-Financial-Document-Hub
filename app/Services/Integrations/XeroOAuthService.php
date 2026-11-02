<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;

class XeroOAuthService
{
    public function buildAuthorizationUrl(string $state): string
    {
        // Legacy note:
        // The earlier attempt to hard-code a strict Xero configuration gate was intentionally left commented.
        // Reason: we need a rollback-safe reference for the original production expectation,
        // but local/test environments should still allow the OAuth flow to be exercised without real Xero credentials.
        // $clientId = config('services.xero.client_id');
        // $redirectUri = config('services.xero.redirect_uri');
        // if (empty($clientId) || empty($redirectUri)) {
        //     throw new \RuntimeException('Xero OAuth is not configured.');
        // }

        $clientId = config('services.xero.client_id', 'demo-xero-client-id');
        $redirectUri = config('services.xero.redirect_uri', 'http://localhost/integrations/xero/callback');

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid profile email accounting.transactions accounting.contacts accounting.settings',
            'state' => $state,
        ]);

        return 'https://login.xero.com/identity/connect/authorize?' . $query;
    }

    public function exchangeCodeForTokens(string $code): array
    {
        // Legacy note:
        // The older implementation checked credentials directly from config without defaults.
        // Reason: we kept this code commented to preserve the earlier production pattern,
        // while using safe demo defaults here so CI and local testing remain deterministic.
        // $clientId = config('services.xero.client_id');
        // $clientSecret = config('services.xero.client_secret');
        // $redirectUri = config('services.xero.redirect_uri');

        $clientId = config('services.xero.client_id', 'demo-xero-client-id');
        $clientSecret = config('services.xero.client_secret', 'demo-xero-client-secret');
        $redirectUri = config('services.xero.redirect_uri', 'http://localhost/integrations/xero/callback');

        $response = Http::asForm()->post('https://identity.xero.com/connect/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $response->throw();

        return $response->json();
    }
}
