<?php

namespace colq2\Tests\Keycloak\Integration;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\Contracts\UserService;
use colq2\Keycloak\DefaultAuthenticator;
use colq2\Tests\Keycloak\Stubs\KeycloakUser;
use colq2\Tests\Keycloak\Stubs\StubAuthenticator;
use colq2\Tests\Keycloak\Traits\FakeGateway;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use League\OAuth2\Client\Token\AccessToken;
use Mockery;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak as KeycloakProvider;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;

class AuthenticatorTest extends TestCase
{
    use FakeGateway;

    /**
     * @var Authenticator $authenticator
     */
    private $authenticator;

    /**
     * @var TokenStorage $tokenStorage ;
     */
    private $tokenStorage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authenticator = $this->app->make(Authenticator::class);

        $this->tokenStorage = $this->app->make(TokenStorage::class);
    }


    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function testHandleRedirect()
    {
        $response = $this->authenticator->handleRedirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $response = new TestResponse($response);
        $response->assertSee(config('keycloak.client_id'));
        $response->assertSee(config('keycloak.base_url'));
        $response->assertStatus(302);

    }

    public function testCallbackCreatesAndAuthenticatesUsesAndStoresTokens()
    {

        $userArray = [
            'sub' => 'subject-id',
            'preferred_username' => 'johndoe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'picture' => '',
        ];
        $resourceOwner = new KeycloakResourceOwner($userArray);

        $accessToken = new AccessToken([
            'access_token' => 'access_token',
            'refresh_token' => 'refresh_token',
            'resource_owner_id' => 1,
            'expires_in' => 300
        ]);

        $provider = Mockery::mock(KeycloakProvider::class);
        $provider->shouldReceive('getAccessToken')
            ->with('authorization_code', ['code' => 'code'])
            ->once()
            ->andReturn($accessToken);

        $provider->shouldReceive('getResourceOwner')
            ->with($accessToken)
            ->andReturn($resourceOwner);

        $state = 'state';
        $this->session([DefaultAuthenticator::STATE_KEY => $state]);
        $request = Request::create('/callback', 'GET', ['code' => 'code', 'state' => $state]);
        $request->setLaravelSession(session());
        $authenticator = new StubAuthenticator(
            app()->make(UserService::class),
            app()->make(TokenStorage::class),
            $provider,
            app()->make(Session::class),
            $request
        );

        $authenticator->handleCallback();

        $user = auth()->user();

        $this->assertNotNull($user);
        $this->assertInstanceOf(KeycloakUser::class, $user);

        $this->assertAuthenticated('keycloak');
        // Test it is in the database
        $this->assertDatabaseHas('users', [
            'sub' => 'subject-id',
            'username' => 'johndoe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'picture' => ''
        ]);

        $tokens = $this->tokenStorage->getAllTokens();
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('id_token', $tokens);
    }

    public function testAuthenticateUser()
    {
        $authenticator = $this->app->make(Authenticator::class);

        $keycloakUser = new KeycloakUser([
            'sub' => 'subject',
            'username' => 'jondoe'
        ]);
        $keycloakUser->save();

        $authenticator->authenticateUser($keycloakUser);

        // Assert that user is authenticated
        $this->assertAuthenticatedAs($keycloakUser, 'keycloak');
    }
}
