<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\SessionTokenStorage;

class SessionTokenStorageTest extends TokenStorageTestCase
{

    /**
     * @var SessionTokenStorage $storage
     */
    protected $storage;

    /**
     * setup environment
     */
    protected function setUp()
    {
        parent::setUp();

        $this->startSession();
    }

    /**
     * @return TokenStorage
     */
    protected function provideStorageInstance()
    {
        return new SessionTokenStorage($this->app['session']->driver());
    }


    public function testItStoresAccessTokenToSession()
    {
        $this->storage->storeAccessToken($this->token);

        $this->assertSame($this->token, session()->get($this->storage::ACCESS_TOKEN_KEY_NAME));
    }

    public function testItStoresRefreshTokenToSession()
    {
        $this->storage->storeRefreshToken($this->token);

        $this->assertSame($this->token, session()->get($this->storage::REFRESH_TOKEN_KEY_NAME));
    }

    public function testItStoresIdTokenToSession()
    {
        $this->storage->storeIdToken($this->token);

        $this->assertSame($this->token, session()->get($this->storage::ID_TOKEN_KEY_NAME));
    }

    public function testItStoresStringTokenToSession()
    {
        $this->storage->store('token', 'testToken');

        $this->assertSame('token', session()->get('testToken'));
    }
}