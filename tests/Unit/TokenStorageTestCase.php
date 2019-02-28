<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Tests\Keycloak\TestCase;
use Lcobucci\JWT\Token;

abstract class TokenStorageTestCase extends TestCase
{
    /**
     * The storage instance
     *
     * @var TokenStorage $storage
     */
    protected $storage;

    /**
     * Token to store
     *
     * @var $token
     */
    protected $token;

    protected function setUp()
    {
        parent::setUp();

        $this->storage = $this->provideStorageInstance();

        $this->token = $this->generateJWTToken();
    }

    /**
     * @return TokenStorage
     */
    protected abstract function provideStorageInstance();

    public function testItStoresAndRetrievesToken()
    {
        $this->storage->store($this->token, 'token');

        $this->assertSame($this->token, $this->storage->get('token'));

        $this->storage->store('stringToken', 'token');

        $this->assertSame('stringToken', $this->storage->get('token'));
    }

    public function testItStoresAndRetrievesAccessToken()
    {
        $this->storage->storeAccessToken($this->token);

        $this->assertSame($this->token, $this->storage->getAccessToken());
    }

    public function testItStoresAndRetrievesRefreshToken()
    {
        $this->storage->storeRefreshToken($this->token);

        $this->assertSame($this->token, $this->storage->getRefreshToken());
    }

    public function testItStoresAndRetrievesIdToken()
    {
        $this->storage->storeIdToken($this->token);

        $this->assertSame($this->token, $this->storage->getIdToken());
    }


    public function testItEmptiesStorage()
    {
        $this->storage->store('token', 'testToken');
        $this->storage->storeAccessToken('accessToken');
        $this->storage->storeRefreshToken('refreshToken');
        $this->storage->storeIdToken('idToken');

        $this->storage->empty(['testToken']);

        $this->assertNull($this->storage->get('testToken'));
        $this->assertNull($this->storage->getAccessToken());
        $this->assertNull($this->storage->getRefreshToken());
        $this->assertNull($this->storage->getIdToken());
    }

    public function testItStoresAndRetrievesAllTokens()
    {
        $this->storage->storeAll('accessToken', 'refreshToken', 'idToken');
        $tokens = $this->storage->getAllTokens();

        $this->assertSame([
            'access_token' => 'accessToken',
            'refresh_token' => 'refreshToken',
            'id_token' => 'idToken'
        ], $tokens);
    }

    protected function generateJWTToken()
    {

        $token = new Token(['alg' => 'none'], ['test' => 'test']);

        return $token;
    }
}