<?php

namespace colq2\Tests\Keycloak\Unit;


use colq2\Keycloak\Http\Middleware\CheckRealmAccess;
use colq2\Keycloak\Http\Middleware\CheckResourceAccess;
use colq2\Keycloak\Roles\RoleChecker;
use colq2\Tests\Keycloak\Stubs\KeycloakUser;
use colq2\Tests\Keycloak\TestCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MiddlewareTest extends TestCase
{

    /**
     * @var CheckRealmAccess
     */
    private $realmAccessMiddleware;

    /**
     * @var CheckResourceAccess
     */
    private $resourceAccessMiddleware;

    /**
     * @var KeycloakUser $user
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->realmAccessMiddleware = new CheckRealmAccess(
            $this->app->make(RoleChecker::class)
        );
        $this->resourceAccessMiddleware = new CheckResourceAccess(
            $this->app->make(RoleChecker::class)
        );

        $this->user = $this->generateUser();
    }

    public function testRealmAccessThrowsAuthenticationException()
    {
        $request = new Request();

        $this->expectException(AuthenticationException::class);
        $this->realmAccessMiddleware->handle($request, function () {
        }, ['role1']);
    }


    public function testResourceAccessThrowsAuthenticationException()
    {
        $request = new Request();

        $this->expectException(AuthenticationException::class);
        $this->resourceAccessMiddleware->handle($request, function () {
        }, 'client', ['role1']);
    }

    public function testRealmAccessMiddlewareWontAbortOnSingleRole()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $response = $this->realmAccessMiddleware->handle($request, function () {
        }, 'offline_access');

        $this->assertNull($response);

    }

    public function testRealmAccessMiddlewareWontAbortOnMultipleRoles()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $response = $this->realmAccessMiddleware->handle($request, function () {
        }, 'offline_access', 'uma_authorization');

        $this->assertNull($response);
    }

    public function testRealmAccessAbortOnWrongRoles()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $this->expectException(HttpException::class);
        $this->realmAccessMiddleware->handle($request, function () {
        }, 'offline_access', 'uma_authorization', 'not_allowed');
    }

    public function testResourceAccessWontAbortOnSingleClientAndRole()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $response = $this->resourceAccessMiddleware->handle($request, function () {
        }, 'test-client', 'update-test');

        $this->assertNull($response);
    }

    public function testResourceAccessWontAbortOnSingleClientAndMultipleRoles()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $response = $this->resourceAccessMiddleware->handle($request, function () {
        }, 'test-client', 'update-test', 'view-test');

        $this->assertNull($response);
    }

    public function testResourceAccessAbortOnSingleClientAndWrongRole()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $this->expectException(HttpException::class);

        $this->resourceAccessMiddleware->handle($request, function () {
        }, 'test-client', 'update-test', 'view-test', 'manage-test');
    }


    public function testRealmAccessAbortsOnEmptyRole()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $this->expectException(HttpException::class);
        $this->realmAccessMiddleware->handle($request, function () {
        });
    }

    public function testResourceAccessAbortsOnEmptyClient()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $this->expectException(HttpException::class);
        $this->resourceAccessMiddleware->handle($request, function () {
        });
    }

    public function testResourceAccessAbortsOnEmptyRole()
    {
        $request = new Request();
        $this->actingAs($this->user);

        $this->expectException(HttpException::class);
        $this->resourceAccessMiddleware->handle($request, function () {
        });
    }
}