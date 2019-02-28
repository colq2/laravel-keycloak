<?php

namespace colq2\Tests\Keycloak\Traits;

use colq2\Keycloak\Contracts\Gateway;
use colq2\Tests\Keycloak\Factories\JWTFactory;
use colq2\Tests\Keycloak\Factories\KeyPairFactory;
use colq2\Tests\Keycloak\Stubs\KeyPair;
use Faker\Factory as Faker;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Mockery;

trait FakeGateway
{
    protected $privateKey;

    protected $publicKey;

    protected $code = '6b649692-f657-4d27-981b-5e66fefc0413.c4a9da36-11d1-45e4-b1af-9c2e87d1f4ae.06c70ebf-9875-4b02-a200-d8624db313e2';

    public function fakeGateway()
    {
        $this->generateKeyPair();

        $fakeGateway = $this->createFakeGateway();

        app()->instance(Gateway::class, $fakeGateway);
    }

    /**
     * Create a fake gateway
     *
     * @return Gateway|Mockery\MockInterface
     */
    protected function createFakeGateway()
    {
        $fakeGateway = Mockery::mock(Gateway::class);

        $fakeGateway->allows('getAccessTokenResponse')//->with($url, $fields)
        ->andReturn($this->generateAccessTokenResponse());

        $fakeGateway->allows('fetchPublicKey')
            ->andReturn($this->generateFetchPublicKeyResponse());

        $fakeGateway->allows('getBaseUrlWithRealm')
            ->andReturn(config('keycloak.base_url') . '/realms/' . config('keycloak.realm'));

        return $fakeGateway;
    }

    protected function generateFetchPublicKeyResponse()
    {
        $startPos = strpos($this->publicKey, "\n");
        $endPos = strpos($this->publicKey, "-----BEGIN PUBLIC KEY-----\n");

        return substr($this->publicKey, $startPos + 1, $endPos - $startPos);
    }

    protected function generateAccessTokenResponse()
    {
        // Generate access token
        $accessToken = $this->generateAccessToken();
        // Generate refresh token
        $refreshToken = $this->generateRefreshToken();

        $faker = Faker::create();

        $response = [
            "access_token" => $accessToken,
            "expires_in" => 300,
            "refresh_expires_in" => 1800,
            "refresh_token" => $refreshToken,
            "token_type" => "bearer",
            "not-before-policy" => 0,
            "session_state" => $faker->uuid,
            "scope" => "openid email profile",
        ];

        return $response;
    }

    /**
     * Create an access token
     *
     * @return string
     */
    protected function generateAccessToken()
    {
        $builder =  JWTFactory::createBuilder([

        ],$this->generateUser());

        $builder->sign(new Sha512(), $this->privateKey);

        return (string)$builder->getToken();
    }

    /**
     * Create a refresh token
     *
     * @return string
     */
    protected function generateRefreshToken()
    {
        $builder = JWTFactory::createBuilder([
            'expires_in' => 3000,
            'typ' => 'Refresh'
        ]);

        $builder->sign(new Sha512(), $this->privateKey);

        return (string)$builder->getToken();
    }

    protected function generateKeyPair()
    {
        $keyPair = KeyPairFactory::create();

        $this->privateKey = $keyPair->getPrivateKey();
        $this->publicKey = $keyPair->getPublicKey();
    }

    protected function generateUser(
        $verified = true,
        $preferredUsername = 'johndoe',
        $givenName = 'John',
        $familyName = 'Doe',
        $email = 'john.doe@example.com'
    ) {

        return [
            'email_verified' => $verified,
            'name' => $givenName.' '.$familyName,
            'preferred_username' => $preferredUsername,
            'given_name' => $givenName,
            'family_name' => $familyName,
            'email' => $email,
        ];
    }

}