<?php

namespace colq2\Tests\Keycloak\Factories;

use Faker\Factory as Faker;
use Illuminate\Support\Arr;

class OIDCUserFactory
{
    public static function create(array $custom = [])
    {
        $faker = Faker::create();

        $givenName = Arr::get($custom, 'given_name', $faker->firstName);
        $familyName = Arr::get($custom, 'family_name', $faker->lastName);


        return [

            'sub' => $faker->uuid,
            'name' => $givenName . " " .$familyName,
            // TODO
        ];
    }
}