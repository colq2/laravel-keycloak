<?php

namespace colq2\Keycloak;


use colq2\Keycloak\Contracts\KeyFetcher;

class ConfigKeyFetcher implements KeyFetcher
{

    /**
     * Fetch and return key
     *
     * @return string
     */
    public function fetchKey(): string
    {
        $publicKey = config('keycloak.public_key', "");

        if (empty($publicKey)) {
            return $publicKey;
        }

        return $this->generatePublicKey($publicKey);
    }


    /**
     * Add begin and end to public key
     *
     * @param string $publicKey
     * @return string
     */
    protected function generatePublicKey(string $publicKey)
    {
        if (strpos($publicKey, "-----BEGIN") === false) {
            return "-----BEGIN PUBLIC KEY-----" . PHP_EOL . $publicKey . PHP_EOL . '-----END PUBLIC KEY-----' . PHP_EOL;
        }
        return $publicKey;
    }

}