<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\Contracts\KeyFetcher;
use colq2\Keycloak\Contracts\TokenChecker;
use colq2\Keycloak\Contracts\TokenFinder;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\Contracts\UserService;
use function foo\func;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Lcobucci\JWT\Token;
use League\OAuth2\Client\Provider\AbstractProvider;
use Nexmo\User\User;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

class KeycloakServiceProvider extends ServiceProvider
{
    /**
     *
     */
    public function boot()
    {
        // config
        $this->publishes([
            __DIR__ . '/../config/keycloak.php' => config_path('keycloak.php'),
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../config/keycloak.php', 'keycloak');

        // Setting encryption on OAuth2 Keycloak provider
        $provider = $this->app->make(AbstractProvider::class);
        $keyFetcher = $this->app->make(KeyFetcher::class);

        if($provider instanceof Keycloak){
            $provider->setEncryptionAlgorithm('RS256');
            $provider->setEncryptionKey($keyFetcher->fetchKey());
        }

        // Extending auth
        Auth::extend('keycloak', function (Container $app, $name, array $config) {
            return new KeycloakGuard(
                Auth::createUserProvider($config['provider']),
                $app->make(TokenStorage::class),
                $app->make(TokenChecker::class),
                $app->make(TokenFinder::class),
                $app->make(UserService::class),
                $app->make(Gateway::class)
            );
        });


        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'keycloak');
    }

    /**
     *
     */
    public function register()
    {

        $this->app->singleton(AbstractProvider::class, function (Container $app) {
            return new Keycloak([
                'authServerUrl' => $app['config']->get('keycloak.base_url'),
                'realm' => $app['config']->get('keycloak.realm'),
                'clientId' => $app['config']->get('keycloak.client_id'),
                'clientSecret' => $app['config']->get('keycloak.client_secret'),
                'redirectUri' => $app['config']->get('keycloak.redirect'),
            ]);
        });

        $this->app->singleton(UserService::class, function (Container $app) {
            return new KeycloakUserService(
                $app['config']->get('keycloak.model', KeycloakUser::class)
            );
        });

        $this->app->singleton(KeyFetcher::class, function(Container $app){
           return new ConfigKeyFetcher();
        });

        $this->app->bind(Authenticator::class, function (Container $app) {
            return new DefaultAuthenticator(
                $app->make(UserService::class),
                $app->make(TokenStorage::class)
            );
        });

        $this->app->bind(TokenFinder::class, function () {
            return new KeycloakTokenFinder(
                $this->app->make(TokenStorage::class)
            );
        });

        $this->app->bind(TokenStorage::class, function (Container $app) {
            return new SessionTokenStorage($app->make(Session::class));
        });

        $this->app->bind(TokenChecker::class, function () {
            return new KeycloakTokenChecker(
                $this->app->make(Gateway::class)
            );
        });

        $this->app->bind(Gateway::class, function (Container $app) {
            return new KeycloakGateway();
        });

    }
}