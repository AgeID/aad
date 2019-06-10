<?php

namespace AgeId\Aad;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class AzureActiveDirectoryHelper
{
    private $guzzle;
    private $clientId;
    private $tenant;
    private $tenantConfigUrl = 'https://login.windows.net/__tenant__/.well-known/openid-configuration';
    private $tokenGetUrl = 'https://login.windows.net/__tenant__/oauth2/token';
    private $tenantConfigs;
    private $jwt;
    private $roles = [];

    function __construct(string $clientId, string $tenant, array $options = [])
    {
        $this->clientId = $clientId;
        $this->tenant = $tenant;
        $this->tenantConfigUrl = str_replace('__tenant__', $tenant, $this->tenantConfigUrl);
        $this->tokenGetUrl = str_replace('__tenant__', $tenant, $this->tokenGetUrl);
        $this->guzzle = $options['httpClient'] ?? new Client();
    }

    public function isValid(string $token) : bool
    {
        try {
            $keys = $this->getKeys();
            $payload = JWT::decode($token, $keys, ['RS256']);
            if ($this->clientId != $payload->aud && $this->clientId != $payload->appid) {
                throw new \RuntimeException('The client_id / audience is invalid!');
            }
            if ($payload->nbf > time() || $payload->exp < time()) {
                // Additional validation is being performed in firebase/JWT itself
                throw new \RuntimeException('The id_token is invalid!');
            }
            if ($payload->iss != $this->tenantConfigs['issuer']) {
                throw new \RuntimeException('Invalid token issuer!');
            }
            if (isset($payload->roles)) {
                $this->roles = $payload->roles;
            }

            $this->jwt = $payload;

        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    public function isValidWithRoles(string $token, array $roles) : bool
    {
        return $this->isValid($token) && count(array_intersect($roles, $this->roles)) == count($roles);
    }

    public function getToken(string $clientKey, string $resource)
	{
        $params = [
            'grant_type' => 'client_credentials',
            'client_secret' => $clientKey,
            'resource' => $resource,
            'client_id' => $this->clientId,
        ];

        $response = $this->guzzle->post(
            $this->tokenGetUrl,
            [RequestOptions::FORM_PARAMS => $params]
        )->getBody()->getContents();

        return json_decode($response);
    }

    public function getJwt(): \stdClass
    {
        return $this->jwt;
    }

    private function getKeys() : array
    {
        $keysUrl = $this->getKeysUrl();
        $obj = $this->parseUrl($keysUrl);

        foreach ($obj['keys'] as $i => $keyinfo) {
            if (isset($keyinfo['x5c']) && is_array($keyinfo['x5c'])) {
                foreach ($keyinfo['x5c'] as $encodedkey) {
                    $key = "-----BEGIN CERTIFICATE-----\n";
                    $key .= wordwrap($encodedkey, 64, "\n", true);
                    $key .= "\n-----END CERTIFICATE-----";
                    $obj[$keyinfo['kid']] = $key;
                }
            }
        }

        return $obj;
    }

    private function getKeysUrl() : string
    {
        $this->tenantConfigs = $this->parseUrl($this->tenantConfigUrl);
        return $this->tenantConfigs['jwks_uri'];
    }

    private function parseUrl(string $url) : array
    {
        $response = $this->guzzle->get($url)->getBody()->getContents();
        return json_decode($response, true);
    }

}
