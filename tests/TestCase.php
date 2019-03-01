<?php

namespace colq2\Tests\Keycloak;

use colq2\Keycloak\KeycloakServiceProvider;
use colq2\Tests\Keycloak\Stubs\KeycloakUser;
use colq2\Tests\Keycloak\Traits\FakeGateway;
use Dotenv\Dotenv;
use Laravel\Socialite\SocialiteServiceProvider;

class TestCase extends \Orchestra\Testbench\Dusk\TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->artisan('migrate', ['--database' => 'testbench'])
            ->run();
    }

    /**
     * Provide package service provider
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array|string
     */
    protected function getPackageProviders($app)
    {
        return [
            KeycloakServiceProvider::class,
            SocialiteServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $dotenv = new Dotenv(__DIR__.'/..');

        $dotenv->load();
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth', [
            'defaults' => ['guard' => 'keycloak'],
            'guards' => [
                'keycloak' => [
                    'driver' => 'keycloak',
                    'provider' => 'users',
                ],
            ],
            'providers' => [
                'users' => [
                    'driver' => 'eloquent',
                    'model' => KeycloakUser::class,
                ],
            ],
            'passwords' => [
                'users' => [
                    'provider' => 'users',
                    'table' => 'password_resets',
                    'expire' => 60,
                ],
            ],

        ]);

        $app['config']->set('keycloak', [
            'model' => env('KEYCLOAK_USER_MODEL', KeycloakUser::class),
            'client_id' => env('KEYCLOAK_CLIENT_ID'),
            'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
            'redirect' => env('KEYCLOAK_REDIRECT'),
            'realm' => env('KEYCLOAK_REALM'),
            'base_url' => env('KEYCLOAK_BASE_URL'),
            'test_integration' => env('TEST_INTEGRATION', false),
        ]);
    }

    protected function setUpTraits()
    {
        $uses = parent::setUpTraits();

        if (isset($uses[FakeGateway::class])) {
            $this->fakeGateway();
        }

    }
}