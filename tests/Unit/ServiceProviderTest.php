<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\Contracts\TokenChecker;
use colq2\Keycloak\Contracts\TokenFinder;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\Contracts\UserService;
use colq2\Keycloak\KeycloakGuard;
use colq2\Keycloak\KeycloakProvider;
use colq2\Tests\Keycloak\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Socialite\Contracts\Factory as SocialiteContract;

class ServiceProviderTest extends TestCase
{
    use DatabaseMigrations;

    public function testSocialiteHelperFunction()
    {
        $this->assertInstanceOf(SocialiteContract::class, socialite());
    }

    public function testProviderExtendsSocialiteWithKeycloakProvider()
    {
        $provider = socialite()->driver('keycloak');

        $this->assertInstanceOf(KeycloakProvider::class, $provider);
    }

    public function testProviderExtendsAuthWithKeycloakGuard()
    {
        $guard = auth()->guard('keycloak');

        $this->assertInstanceOf(KeycloakGuard::class, $guard);
    }

    public function testProviderRegistersUserService()
    {
        $this->assertProviderRegisters(UserService::class);

        $userService1 = $this->app->make(UserService::class);
        $userService2 = $this->app->make(UserService::class);

        self::assertSame($userService1, $userService2);
    }

    public function testProviderRegistersAuthenticator()
    {
        $this->assertProviderRegisters(Authenticator::class);
    }

    public function testProviderRegistersTokenFinder()
    {
        $this->assertProviderRegisters(TokenFinder::class);
    }

    public function testProviderRegistersTokenStorage()
    {
        $this->assertProviderRegisters(TokenStorage::class);
    }

    public function testProviderRegistersTokenChecker()
    {
        $this->assertProviderRegisters(TokenChecker::class);
    }

    public function testProviderRegistersGateway()
    {
        $this->assertProviderRegisters(Gateway::class);
    }

    protected function assertProviderRegisters($class)
    {
        $this->assertInstanceOf($class,
            $this->app->make($class)
        );
    }

}