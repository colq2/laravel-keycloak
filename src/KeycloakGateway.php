<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Gateway;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Arr;

class KeycloakGateway implements Gateway
{
    /**
     * A guzzle client
     * @var Client $httpClient
     */
    private $httpClient;

    /**
     * @var array $guzzle
     */
    private $guzzle;

    /**
     * KeycloakGateway constructor.
     *
     * @param array $guzzle
     */
    public function __construct($guzzle = [])
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Get the access token response for the given code.
     *
     * @param array $fields
     * @return array
     */
    public function getAccessTokenResponse(array $fields)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()
            ->post($this->getTokenUrl(), [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                $postKey => $fields,
            ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Fetch the public key from keycloak server
     *
     * @return \Illuminate\Contracts\Cache\Repository|string|null
     */
    public function fetchPublicKey()
    {
        $response = $this->getHttpClient()
            ->get($this->getBaseUrlWithRealm());

        $json = json_decode($response->getBody(), true);

        return Arr::get($json, 'public_key');
    }

    /**
     * Try to get access token by refresh token
     *
     * @param string $refreshToken
     * @return array|mixed
     */
    public function getRefreshTokenResponse(string $refreshToken)
    {
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';

        $response = $this->getHttpClient()
            ->post($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                $postKey => $this->getRefreshTokenFields($refreshToken),
            ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get a instance of the Guzzle HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        if (is_null($this->httpClient)) {
            $this->httpClient = new Client($this->guzzle);
        }

        return $this->httpClient;
    }

    /**
     * Returns the base url with the specified realm
     *
     * @return string
     */
    public function getBaseUrlWithRealm()
    {
        return config('keycloak.base_url') . '/realms/' . config('keycloak.realm');
    }

    /**
     * Return the token url
     *
     * @return string
     */
    public function getTokenUrl()
    {
        return $this->getBaseUrlWithRealm() . '/protocol/openid-connect/token';
    }

    /**
     * Return fields for refresh token request
     *
     * @param string $refreshToken
     * @return array
     */
    protected function getRefreshTokenFields(string $refreshToken){
        return [
            'client_id' => config('keycloak.client_id'),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];
    }
}