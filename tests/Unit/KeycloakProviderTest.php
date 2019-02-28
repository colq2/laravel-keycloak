<?php

namespace colq2\Tests\Keycloak\Unit;


use colq2\Keycloak\Contracts\UserService;
use colq2\Keycloak\KeycloakProvider;
use colq2\Tests\Keycloak\TestCase;

class KeycloakProviderTest extends TestCase
{

    /**
     * @var KeycloakProvider $provider
     */
    private $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->provider = socialite()->with('keycloak');
    }

    public function testProviderReturnsUserServices(){
        $this->assertInstanceOf(UserService::class, $this->provider->getUserService());
    }
}