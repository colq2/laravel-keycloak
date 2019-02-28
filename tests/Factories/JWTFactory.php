<?php

namespace colq2\Tests\Keycloak\Factories;

use colq2\Keycloak\SignerFactory;
use Faker\Factory as Faker;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Builder;

class JWTFactory
{

    public static function createBuilder($data = [], $user = null)
    {

//        $expiration = 300, $typ = 'Bearer'
        $builder = new Builder();
        $faker = Faker::create();
        $azp = Arr::get($data, 'azp');

        $builder->setHeader('alg', Arr::get($data, 'alg', 'RS256'));
        $builder->setAudience(Arr::get($data, 'aud'));
        $builder->setExpiration(Arr::get($data, 'exp', time() + 300));
        $builder->setId(Arr::get($data, 'jti', $faker->uuid));
        $builder->setIssuedAt(Arr::get($data, 'iat', time()));
        $builder->setIssuer(Arr::get($data, 'iss', $faker->url));
        $builder->setNotBefore(Arr::get($data, 'nbf', 0));
        $builder->setSubject(Arr::get($data, 'sub', $faker->uuid));
        $builder->set('session_state', Arr::get($data, 'session_state', $faker->uuid));
        $builder->set('typ', Arr::get($data, 'typ', 'Bearer'));
        $azp ? $builder->set('azp',  $azp) : null;
        $builder->set('auth_time', Arr::get($data, 'auth_time', time()));
        $builder->set('acr', Arr::get($data, 'acr', 0));
        $builder->set('realm_access', [
            'roles' => ['offline_access', 'uma_authorization'],
        ]);
        $builder->set('resource_access', [
            'account' => [
                'roles' => [
                    'manage-account',
                    'manage-account-links',
                    'view-profile',
                ],
            ],
        ]);
        $builder->set('scope', 'openid email profile');

        if ($user) {
            // TODO: Build by User
            $builder->set('email_verified', Arr::get($user, 'email_verified'));
            $builder->set('name', Arr::get($user, 'name'));
            $builder->set('preferred_username', Arr::get($user, 'preferred_username'));
            $builder->set('given_name', Arr::get($user, 'given_name'));
            $builder->set('family_name', Arr::get($user, 'family_name'));
            $builder->set('email', Arr::get($user, 'email'));
        }

        return $builder;
    }

    public static function create($data = [], $user = null){
        $keyPair = KeyPairFactory::create();
        $builder = self::createBuilder($data, $user);

        $builder->sign(SignerFactory::create('RS256'), $keyPair->getPrivateKey());

        return $builder->getToken();
    }
}