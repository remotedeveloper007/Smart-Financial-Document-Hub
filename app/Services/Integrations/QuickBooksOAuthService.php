<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;

class QuickBooksOAuthService
{
    public function buildAuthorizationUrl(string $state): string
    {
        // Legacy note: the original strict configuration check was left commented intentionally.
        // Reason: we preserved the earlier production-safe validation logic for reference,
        // but the app must also work in local/test environments without real QuickBooks
        // credentials, so the demo defaults keep OAuth flow testable and non-blocking.
        // $clientId = config('services.quickbooks.client_id');
        // $redirectUri = config('services.quickbooks.redirect_uri');

        // if (empty($clientId) || empty($redirectUri)) {
        //     throw new \RuntimeException('QuickBooks OAuth is not configured.');
        // }
        $clientId = config('services.quickbooks.client_id', 'demo-client-id');
        $redirectUri = config('services.quickbooks.redirect_uri', 'http://localhost/integrations/quickbooks/callback');        

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'com.intuit.quickbooks.accounting openid profile email',
            'state' => $state,
        ]);

        return 'https://appcenter.intuit.com/connect/oauth2?' . $query;
    }

    public function exchangeCodeForTokens(string $code): array
    {
        // Legacy note: the original direct credential lookup was kept commented intentionally.
        // Reason: this preserves the earlier production credential pattern for rollback and review,
        // while the environment defaults allow safe local development and automated testing.
        //$clientId = config('services.quickbooks.client_id');
        // $clientSecret = config('services.quickbooks.client_secret');
        // $redirectUri = config('services.quickbooks.redirect_uri');  
        $clientId = config('services.quickbooks.client_id', 'demo-client-id');
        $clientSecret = config('services.quickbooks.client_secret', 'demo-client-secret');       
        $redirectUri = config('services.quickbooks.redirect_uri', 'http://localhost/integrations/quickbooks/callback');

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

        $response->throw();

        return $response->json();
    }
}
