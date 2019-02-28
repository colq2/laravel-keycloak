<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\KeycloakProvider;
use colq2\Tests\Keycloak\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Socialite\Contracts\Factory as SocialiteContract;

class ServiceProviderTest extends TestCase
{
    use DatabaseMigrations;

    public function testSocialiteHelperFunction()
    {
        $this->assertInstanceOf(SocialiteContract::class, socialite());
    }

    public function testProviderExtendsSocialiteWithCorrectParameters()
    {
        $provider = socialite()->driver('keycloak');

        $this->assertInstanceOf(KeycloakProvider::class, $provider);

        if($provider instanceof KeycloakProvider);

    }

}