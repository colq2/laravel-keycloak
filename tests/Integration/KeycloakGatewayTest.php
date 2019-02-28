<?php

namespace colq2\Tests\Keycloak\Integration;

use colq2\Keycloak\Contracts\Gateway;

class KeycloakGatewayTest extends TestCase
{

    /**
     * @var Gateway $gateway
     */
    private $gateway;

    protected function setUp()
    {
        parent::setUp();

        $this->gateway = $this->app->make(Gateway::class);

    }

    public function testIntegrationTestConfig()
    {
        if (!config('keycloak.test_integration')) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
    }

    public function testGetTokenResponse()
    {
        $response = $this->gateway->getAccessTokenResponse([
            'client_id' => config('keycloak.client_id'),
            'client_secret' => config('keycloak.client_secret'),
            'redirect_uri' => config('keycloak.redirect'),
            'grant_type' => 'implicit',
        ]);

        dd($response);

        $this->visit();
    }
}