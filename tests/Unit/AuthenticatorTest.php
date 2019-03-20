<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\DefaultAuthenticator;
use colq2\Keycloak\KeycloakGuard;
use colq2\Tests\Keycloak\Stubs\KeycloakUser;
use colq2\Tests\Keycloak\TestCase;
use Mockery;

class AuthenticatorTest extends TestCase
{
    /**
     * @var DefaultAuthenticator $authenticator
     */
    private $authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticator = $this->app->make(Authenticator::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCustomScopesCanBeSet()
    {
        $scopes = ['scope1', 'scope2'];
        $this->authenticator->withScopes($scopes);

        $this->assertSame($scopes, $this->authenticator->getScopes());
    }

    public function testAuthenticateUserSetsUserOnGuard()
    {
        $user = new KeycloakUser([
            'sub' => 'sub'
        ]);

        $keycloakGuard = Mockery::mock(KeycloakGuard::class);
        $keycloakGuard->shouldReceive('setUser')
            ->with($user)
            ->once();

        $keycloakGuard->shouldReceive('user')
            ->andReturn($user);

        auth()->extend('keycloak', function () use ($keycloakGuard) {
            return $keycloakGuard;
        });

        $this->authenticator->authenticateUser($user);
        $this->assertSame(auth()->user(), $user);
    }
}