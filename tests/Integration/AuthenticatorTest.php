<?php

namespace colq2\Keycloak\Test\Integration;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Tests\Keycloak\Factories\OIDCUserFactory;
use colq2\Tests\Keycloak\Integration\TestCase;
use colq2\Tests\Keycloak\Stubs\KeycloakProviderStub;
use colq2\Tests\Keycloak\Traits\FakeGateway;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;
use stdClass;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthenticatorTest extends TestCase
{
    use FakeGateway;

    /**
     * @var Authenticator $authenticator
     */
    private $authenticator;

    protected function setUp()
    {
        parent::setUp();

        $this->authenticator = $this->app->make(Authenticator::class);
    }


    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testHandleRedirect()
    {
        // Create Request to redirect
        $request = Request::create('foo');
        $request->setLaravelSession($session = Mockery::mock(Session::class));
        $session->shouldReceive('put')
            ->once();

        $provider = new KeycloakProviderStub($request, 'client_id', 'client_secret', 'redirect');

        $response = $provider->redirect();
        $this->assertInstanceOf(RedirectResponse::class, $response);

        dd($response->getTargetUrl());
    }

    public function testHandleCallback()
    {
        // Create Request

        $parameters = [
            "state" => "hd4eIjaWHpzOj91CwBu2lxdSbrxAhsGWNo02jqWX",
            "session_state" => "c4a9da36-11d1-45e4-b1af-9c2e87d1f4ae",
            "code" => $this->code,
        ];

        $request = new Request($parameters, $parameters);

        // Create KeycloakProvider with this request
        $keycloakDriver = socialite()->driver('keycloak');

        $keycloakDriver->setRequest($request);

        $response = $this->authenticator->handleCallback();

        // Validate that user ist logged in

        // Validate that we are redircted to home

        $testResponse = new TestResponse($response);

        $testResponse->assertStatus(302);
    }

    public function testCallbackCreatesAndAuthenticatesUses()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('A', 40), 'code' => 'code']);
        $request->setSession($session = Mockery::mock(SessionInterface::class));
        $session->shouldReceive('pull')
            ->once()
            ->with('state')
            ->andReturn(str_repeat('A', 40));


        $provider = socialite()->driver('keycloak');
        $provider->setRequest($request);


        $provider->http = Mockery::mock(stdClass::class);
        $postKey = (version_compare(ClientInterface::VERSION, '6') === 1) ? 'form_params' : 'body';
        $provider->http->shouldReceive('post')
            ->once()
            ->with('https://graph.facebook.com/v3.0/oauth/access_token', [
                $postKey => [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                    'code' => 'code',
                    'redirect_uri' => 'redirect_uri',
                ],
            ])
            ->andReturn($response = Mockery::mock(stdClass::class));


        $response->shouldReceive('getBody')
            ->once()
            ->andReturn(json_encode(['access_token' => 'access_token', 'expires' => 5183085]));
        $user = $provider->user();

        $user = OIDCUserFactory::create();

        $token = // Fake request to callback
        $request = Request::create('callback', 'GET', [

        ]);
    }

    public function testAuthenticateUser()
    {

    }
}
