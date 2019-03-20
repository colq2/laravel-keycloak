<?php

namespace colq2\Tests\Keycloak;

use colq2\Keycloak\KeycloakServiceProvider;
use colq2\Keycloak\SignerFactory;
use colq2\Tests\Keycloak\Factories\KeyPairFactory;
use colq2\Tests\Keycloak\Stubs\KeycloakUser;
use colq2\Tests\Keycloak\Traits\FakeGateway;
use Dotenv\Dotenv;
use Dotenv\Environment\Adapter\EnvConstAdapter;
use Dotenv\Environment\Adapter\ServerConstAdapter;
use Dotenv\Environment\DotenvFactory;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Builder;

class TestCase extends \Orchestra\Testbench\Dusk\TestCase
{
    protected function setUp(): void
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
        $factory = new DotenvFactory([new EnvConstAdapter(), new ServerConstAdapter()]);

        $dotenv = Dotenv::create(__DIR__ . '/..', null, $factory);
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


    protected function generateToken()
    {
        $roles = $this->generateRoles();

        $builder = new Builder();
        $builder->set('realm_access', Arr::get($roles, 'realm_access'));
        $builder->set('resource_access', Arr::get($roles, 'resource_access'));

        $keyPair = KeyPairFactory::create();
        $builder->sign(SignerFactory::create('RS256'), $keyPair->getPrivateKey());

        return $builder->getToken();
    }

    protected function generateUser()
    {
        $user = new KeycloakUser([
            'sub' => 'subject',
            'username' => 'johndoe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'picture' => null,
            'roles' => $this->generateRoles()
        ]);

        $user->save();

        return $user->refresh();
    }

    protected function generateRoles()
    {
        return [
            'realm_access' => [
                'roles' => ['offline_access', 'uma_authorization']
            ],
            'resource_access' => [
                'test-client' => [
                    'roles' => ['update-test', 'create-test', 'view-test'] // manage-test, delete-test
                ],
                'test-client2' => [
                    'roles' => ['view-profile']
                ]
            ]
        ];
    }
}