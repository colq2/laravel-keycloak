<?php

namespace colq2\Keycloak\Test;

use colq2\Keycloak\Exceptions\SignerNotFoundException;
use colq2\Keycloak\SignerFactory;
use colq2\Tests\Keycloak\TestCase;

class SignerFactoryTest extends TestCase
{
    public function testCreateSigner()
    {
        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Hmac\Sha256::class, SignerFactory::create('hs256'));
        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Hmac\Sha256::class, SignerFactory::create('HS256'));
        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Hmac\Sha384::class, SignerFactory::create('HS384'));
        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Hmac\Sha512::class, SignerFactory::create('HS512'));

        // Note: The Signer is not working at the moment in the jwt library
        // Will be commented out when a new Version is available
        // See: https://github.com/lcobucci/jwt/issues/259
        //$this->assertInstanceOf(\Lcobucci\JWT\Signer\Ecdsa\Sha256::class, SignerFactory::create('ES256'));
        //$this->assertInstanceOf(\Lcobucci\JWT\Signer\Ecdsa\Sha384::class, SignerFactory::create('ES384'));
        //$this->assertInstanceOf(\Lcobucci\JWT\Signer\Ecdsa\Sha512::class, SignerFactory::create('ES512'));

        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Rsa\Sha256::class, SignerFactory::create('RS256'));
        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Rsa\Sha384::class, SignerFactory::create('RS384'));
        $this->assertInstanceOf(\Lcobucci\JWT\Signer\Rsa\Sha512::class, SignerFactory::create('RS512'));

    }

    public function testCreateThrowsException()
    {

        $this->expectException(SignerNotFoundException::class);

        $test = SignerFactory::create('JOE');
    }
}
