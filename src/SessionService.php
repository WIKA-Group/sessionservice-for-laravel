<?php

namespace WikaGroup\SessionService;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SessionService
{
    private readonly string $tenantId;

    private readonly string $clientId;

    private readonly string $clientSecret;

    private readonly string $baseUrl;

    private readonly string $redirectUri;

    private readonly string $scope;

    public function __construct()
    {
        $config = config('sessionservice.connection');

        $this->tenantId = $config['tenant_id'];
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['secret'];
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->redirectUri = $config['redirect_uri'];
        $this->scope = $config['scope'];
    }

    public function getLoginUrl(): string
    {
        return $this->baseUrl . '/session/auth/login?' . http_build_query([
            'redirect_uri' => $this->redirectUri,
        ]);
    }

    public function getLogoutUrl(): string
    {
        return $this->baseUrl . '/session/auth/logout?' . http_build_query([
            'redirect_uri' => $this->redirectUri,
        ]);
    }

    /**
     * @return array{session_token: string, expires_at: string, user_id: string, email: string, first_name: string, last_name: string, display_name: string}
     *
     * @throws RequestException
     */
    public function exchangeCode(string $code): array
    {
        $token = $this->getClientCredentialsToken();

        return Http::withToken($token)
            ->post($this->baseUrl . '/session/exchange', ['code' => $code])
            ->throw()
            ->json();
    }

    private function getClientCredentialsToken(): string
    {
        return Cache::remember('session-service:cc-token', 3300, function (): string {
            $response = Http::asForm()
                ->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => $this->scope,
                ])
                ->throw();

            return $response->json('access_token');
        });
    }
}
