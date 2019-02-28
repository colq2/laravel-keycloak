<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Exceptions\SignerNotFoundException;

class SignerFactory
{
    /**
     * Array with algorithms_ids to algorithm
     *
     * @var array
     */
    const ALGORITHMS = [
        'HS256' => \Lcobucci\JWT\Signer\Hmac\Sha256::class,
        'HS384' => \Lcobucci\JWT\Signer\Hmac\Sha384::class,
        'HS512' => \Lcobucci\JWT\Signer\Hmac\Sha512::class,
        /**
         * @See: SignerFactoryTest
         */
        //'ES256' => \Lcobucci\JWT\Signer\Ecdsa\Sha256::class,
        //'ES384' => \Lcobucci\JWT\Signer\Ecdsa\Sha384::class,
        //'ES512' => \Lcobucci\JWT\Signer\Ecdsa\Sha512::class,
        'RS256' => \Lcobucci\JWT\Signer\Rsa\Sha256::class,
        'RS384' => \Lcobucci\JWT\Signer\Rsa\Sha384::class,
        'RS512' => \Lcobucci\JWT\Signer\Rsa\Sha512::class,
    ];

    /**
     * @param string $algorithm
     * @return \Lcobucci\JWT\Signer
     * @throws \colq2\Keycloak\Exceptions\SignerNotFoundException
     */
    public static function create(string $algorithm): \Lcobucci\JWT\Signer
    {
        $algorithm = strtoupper($algorithm);

        if (array_key_exists($algorithm, self::ALGORITHMS)) {
            $algorithmClass = self::ALGORITHMS[$algorithm];

            return new $algorithmClass();
        }

        throw new SignerNotFoundException($algorithm);
    }
}