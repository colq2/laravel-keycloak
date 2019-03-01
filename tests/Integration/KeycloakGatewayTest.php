<?php

namespace colq2\Tests\Keycloak\Integration;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\Gateway;
use colq2\Tests\Keycloak\Stubs\KeycloakProviderStub;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;

class KeycloakGatewayTest extends TestCase
{

    /**
     * @var Gateway $gateway
     */
    private $gateway;

    /**
     * @var Authenticator $authenticator +
     */
    private $authenticator;


    protected function setUp()
    {
        parent::setUp();

        $this->gateway = $this->app->make(Gateway::class);
        $this->authenticator = $this->app->make(Authenticator::class);

        $kernel = app('Illuminate\Contracts\Http\Kernel');
        $kernel->pushMiddleware('Illuminate\Session\Middleware\StartSession');

        $this->app['router']->get('redirect', 'colq2\\Tests\\Keycloak\\Stubs\\Http\\Controllers\\LoginController@handleRedirect');
        $this->app['router']->get('callback', 'colq2\\Tests\\Keycloak\\Stubs\\Http\\Controllers\\LoginController@handleCallback');

        \Orchestra\Testbench\Dusk\Options::withoutUI();

        $socialite = socialite();
        $socialite->extend('keycloak', function () use ($socialite) {
            return $socialite->buildProvider(KeycloakProviderStub::class, config('keycloak'));
        });
    }

    public function testIntegrationTestConfig()
    {
        if (!config('keycloak.test_integration')) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
    }

    protected function getUserInfoResponse()
    {
        $tokens = $this->getTokenResponse();

        $response = $this->gateway->getUserInfoResponse($tokens['access_token']);
    }

    protected function getTokenResponse()
    {
        $request = $this->getCallbackRequest();
        $callbackUrl = $request->getRequestUri();

        socialite()->driver('keycloak')->setRequest($request);
        $request->setLaravelSession(session());

        return $this->gateway->getAccessTokenResponse($this->getTokenFields(
            $request->get('code')
        ));
    }

    protected function getCallbackRequest()
    {
        $response = $this->get('redirect');
        $baseResponse = $response->baseResponse;
        $url = $baseResponse->getTargetUrl();
        $callbackUrl = '';

        $this->browse(function (Browser $browser) use ($url, &$callbackUrl) {
            $browser->visit($url)
                ->assertSee('Log In')
                ->type('username', 'test')
                ->type('password', 'secret')
                ->click('#kc-login');

            $callbackUrl = $browser->driver->getCurrentURL();
        });

        $callbackUrl = substr($callbackUrl, strpos($callbackUrl, '/callback'));

        return Request::create($callbackUrl);
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'client_id' => config('keycloak.client_id'),
            'client_secret' => config('keycloak.client_secret'),
            'code' => $code,
            'redirect_uri' => $this->formatRedirectUrl(config('keycloak')),
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * Format the callback URL, resolving a relative URI if needed.
     *
     * @param  array $config
     * @return string
     */
    protected function formatRedirectUrl(array $config)
    {
        $redirect = value($config['redirect']);

        return Str::startsWith($redirect, '/')
            ? $this->app['url']->to($redirect)
            : $redirect;
    }
}