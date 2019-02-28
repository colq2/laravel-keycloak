<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\KeycloakRealmKeyFetcher;
use colq2\Tests\Keycloak\TestCase;
use colq2\Tests\Keycloak\Traits\FakeGateway;

class RealmKeyFetcherTest extends TestCase
{
    use FakeGateway;

    /**
     * @var KeycloakRealmKeyFetcher $fetcher
     */
    private $fetcher;

    protected function setUp()
    {
        parent::setUp();

        $this->fetcher = new KeycloakRealmKeyFetcher($this->app->make(Gateway::class));
    }

    public function testFetchPublicKey()
    {
        $publicKey = $this->fetcher->fetchPublicKey();


        $this->assertSame($this->publicKey, $publicKey);
    }

    public function testFetcherSaveKeyToCache()
    {
        $publicKey = $this->fetcher->fetchPublicKey();

        $this->assertSame($publicKey, cache()->get($this->fetcher::PUBLIC_KEY_CACHE_NAME));
    }
}