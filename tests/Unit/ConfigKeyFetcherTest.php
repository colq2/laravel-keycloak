<?php

namespace colq2\Tests\Keycloak\Unit;


use colq2\Keycloak\ConfigKeyFetcher;
use colq2\Tests\Keycloak\TestCase;

class ConfigKeyFetcherTest extends TestCase
{

    /**
     * @var ConfigKeyFetcher $fetcher
     */
    private $fetcher;

    protected function setUp()
    {
        parent::setUp();

        $this->fetcher = new ConfigKeyFetcher();
        $this->app->make('config')->set('keycloak.public_key', 'PUBLICKEY');
    }

    public function testFetcherFetchesFromConfig()
    {
        $publicKey = $this->fetcher->fetchKey();


        $this->assertSame($this->buildPublicKey('PUBLICKEY'), $publicKey);
    }

    public function testFetcherFetchesNothing()
    {
        $publicKey = $this->app->make('config')->set('keycloak.public_key', '');
        $this->assertEmpty($publicKey);
    }

    protected function buildPublicKey($key)
    {
        return "-----BEGIN PUBLIC KEY-----" . PHP_EOL . $key . PHP_EOL . '-----END PUBLIC KEY-----' . PHP_EOL;
    }

}