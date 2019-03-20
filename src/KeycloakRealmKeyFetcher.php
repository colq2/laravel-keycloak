<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Gateway;

class KeycloakRealmKeyFetcher
{
    /**
     * @var \colq2\Keycloak\KeycloakGateway
     */
    private $gateway;

    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Name for key in cache
     * TODO: Change name to specific realm
     */
    const PUBLIC_KEY_CACHE_NAME = 'realm_public_key';

    /**
     * Fetch the public key from keycloak server
     *
     * @return \Illuminate\Contracts\Cache\Repository|string|null
     */
    public function fetchPublicKey()
    {

        // Try to get from cache
        if ($publicKey = $this->retrieveFromCache()) {
            return $publicKey;
        }

        $publicKey = $this->generatePublicKey($this->gateway->fetchPublicKey());

        $this->putToCache($publicKey);

        return $publicKey;
    }

    /**
     * try to get public key from cache
     *
     * @return \Illuminate\Contracts\Cache\Repository|null
     */
    protected function retrieveFromCache()
    {
        try {
            return cache()->get(self::PUBLIC_KEY_CACHE_NAME, null);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Put public key to cache
     *
     * @param string $publicKey
     */
    protected function putToCache(string $publicKey)
    {
        try {
            cache()->put(self::PUBLIC_KEY_CACHE_NAME, $publicKey, 60 * 60 * 4);
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * Add begin and end to public key
     *
     * @param string $publicKey
     * @return string
     */
    protected function generatePublicKey(string $publicKey)
    {
        return "-----BEGIN PUBLIC KEY-----".PHP_EOL.$publicKey.PHP_EOL.'-----END PUBLIC KEY-----'.PHP_EOL;
    }

    /**
     * Returns the base url with the specified realm
     *
     * @return string
     */
    protected function getBaseUrlWithRealm()
    {

        return config('services.keycloak.base_url').'/realms/'.config('services.keycloak.realm');
    }
}