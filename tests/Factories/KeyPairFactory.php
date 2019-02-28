<?php

namespace colq2\Tests\Keycloak\Factories;


use colq2\Tests\Keycloak\Stubs\KeyPair;

class KeyPairFactory
{

    public static function create(){

        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Create the private and public key
        $res = openssl_pkey_new($config);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        return  new KeyPair($privKey, $pubKey);
    }
}