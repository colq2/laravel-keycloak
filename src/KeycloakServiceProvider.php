<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\Contracts\TokenChecker;
use colq2\Keycloak\Contracts\TokenFinder;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\Contracts\UserService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Lcobucci\JWT\Token;

class KeycloakServiceProvider extends ServiceProvider
{
    /**
     *
     */
    public function boot()
    {
        // config
        $this->publishes([
            __DIR__.'/../config/keycloak.php' => config_path('keycloak.php'),
        ]);

        $this->mergeConfigFrom(__DIR__.'/../config/keycloak.php', 'keycloak');

        // Extending socialite
        $socialite =  socialite(); //$this->app->make(\Laravel\Socialite\Contracts\Factory::class);

        $socialite->extend('keycloak', function (Container $app) use ($socialite) {
            return $socialite->buildProvider(KeycloakProvider::class, config('keycloak'));
        });

        // Extending auth
        Auth::extend('keycloak', function (Container $app, $name, array $config) {
            return new KeycloakGuard(
                Auth::createUserProvider($config['provider']),
                $app->make(TokenStorage::class),
                $app->make(TokenChecker::class),
                $app->make(TokenFinder::class),
                new KeycloakUserService(),
                $app->make(Gateway::class)
            );
        });


        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'keycloak');
    }

    /**
     *
     */
    public function register()
    {
        $this->app->singleton(UserService::class, function(Container $app) {
            return new KeycloakUserService(
                config('keycloak.model')
            );
        });

        $this->app->bind(Authenticator::class, function (Container $app) {
            return new KeycloakAuthenticator(
                $app->make(KeycloakUserService::class),
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

        $this->app->bind(Gateway::class, function(Container $app){
           return new KeycloakGateway();
        });

    }
}