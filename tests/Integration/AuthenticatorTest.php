<?php

namespace colq2\Keycloak\Test\Integration;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\DefaultAuthenticator;
use colq2\Keycloak\KeycloakProvider;
use colq2\Keycloak\SocialiteOIDCUser;
use colq2\Tests\Keycloak\Integration\TestCase;
use colq2\Tests\Keycloak\Stubs\KeycloakProviderStub;
use colq2\Tests\Keycloak\Stubs\KeycloakUser;
use colq2\Tests\Keycloak\Traits\FakeGateway;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;

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

    protected function setUp()
    {
        parent::setUp();

        $this->authenticator = $this->app->make(Authenticator::class);

        $this->tokenStorage = $this->app->make(TokenStorage::class);
    }


    protected function tearDown()
    {
        parent::tearDown();

        Mockery::close();
    }

    public function testHandleRedirect()
    {
        // Create Request to redirect
        $request = Request::create('foo');
        $request->setLaravelSession($session = Mockery::mock(Session::class));
        $session->shouldReceive('put')
            ->once();

        $provider = new KeycloakProviderStub($request, 'client_id', 'client_secret', 'http://localhost/redirect');

        $response = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);

        $response = new TestResponse($response);
        $response->assertSee('client_id');
        $response->assertSee(urlencode('http://localhost/redirect'));
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
        $socialiteUser = new SocialiteOIDCUser();
        $socialiteUser->setRaw($userArray)
            ->map($userArray);

        $provider = Mockery::mock(KeycloakProvider::class);
        $provider->shouldReceive('user')
            ->andReturn($socialiteUser);

        socialite()->extend('keycloak', function () use ($provider) {
            return $provider;
        });

        $authenticator = $this->app->make(DefaultAuthenticator::class);

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

    public function testAllowAddCustomScopes()
    {
        // Create Request to redirect
        $request = Request::create('foo');
        $request->setLaravelSession($session = Mockery::mock(Session::class));
        $session->shouldReceive('put')
            ->once();

        $provider = new KeycloakProviderStub($request, 'client_id', 'client_secret', 'redirect');

        // Socialite should return this provider
        socialite()->extend('keycloak', function () use ($provider) {
            return $provider;
        });

        $this->assertSame($provider, socialite()->driver('keycloak'));

        $authenticator = $this->app->make(Authenticator::class);

        $response = $authenticator->withScopes(['email', 'profile'])->handleRedirect();
        $response = new TestResponse($response);
        $response->assertSee('email');
        $response->assertSee('profile');
    }
}
