<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\OnlineRealmKeyFetcher;
use colq2\Tests\Keycloak\TestCase;
use colq2\Tests\Keycloak\Traits\FakeGateway;

class OnlineKeyFetcherTest extends TestCase
{
    use FakeGateway;

    /**
     * @var OnlineRealmKeyFetcher $fetcher
     */
    private $fetcher;

    protected function setUp()
    {
        parent::setUp();

        $this->fetcher = new OnlineRealmKeyFetcher($this->app->make(Gateway::class));
    }

    public function testFetchPublicKey()
    {
        $publicKey = $this->fetcher->fetchKey();


        $this->assertSame($this->publicKey, $publicKey);
    }

    public function testFetcherSaveKeyToCache()
    {
        $publicKey = $this->fetcher->fetchKey();

        $this->assertSame($publicKey, cache()->get($this->fetcher::PUBLIC_KEY_CACHE_NAME));
    }
}