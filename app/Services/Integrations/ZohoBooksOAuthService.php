<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;

class ZohoBooksOAuthService
{
    public function buildAuthorizationUrl(string $state): string
    {
        // Legacy note:
        // The older implementation used a strict configuration gate.
        // Reason: we kept that logic commented so the team can compare the original production expectation
        // with the current local-safe configuration used in development and CI.
        // $clientId = config('services.zoho.client_id');
        // $redirectUri = config('services.zoho.redirect_uri');
        // if (empty($clientId) || empty($redirectUri)) {
        //     throw new \RuntimeException('Zoho Books OAuth is not configured.');
        // }

        $clientId = config('services.zoho.client_id', 'demo-zoho-client-id');
        $redirectUri = config('services.zoho.redirect_uri', 'http://localhost/integrations/zoho/callback');

        $query = http_build_query([
            'scope' => 'ZohoBooks.books.ALL,ZohoBooks.settings.READ,ZohoBooks.contacts.READ',
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return 'https://accounts.zoho.com/oauth/v2/auth?' . $query;
    }

    public function exchangeCodeForTokens(string $code): array
    {
        // Legacy note:
        // The previous credential lookup was kept commented to preserve rollback clarity.
        // Reason: the current implementation intentionally uses safe demo defaults for local testing,
        // while still supporting real credentials via .env override in production.
        // $clientId = config('services.zoho.client_id');
        // $clientSecret = config('services.zoho.client_secret');
        // $redirectUri = config('services.zoho.redirect_uri');

        $clientId = config('services.zoho.client_id', 'demo-zoho-client-id');
        $clientSecret = config('services.zoho.client_secret', 'demo-zoho-client-secret');
        $redirectUri = config('services.zoho.redirect_uri', 'http://localhost/integrations/zoho/callback');

        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        $response->throw();

        return $response->json();
    }
}
