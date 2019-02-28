<?php

namespace colq2\Keycloak\Examples;

use colq2\Keycloak\KeycloakUserService;
use colq2\Keycloak\SocialiteOIDCUser;

class CustomUserService extends KeycloakUserService
{

    public function mapSocialiteUserToKeycloakUser(SocialiteOIDCUser $user)
    {
        $keycloakUser = (array) $user;

        // DO whatever you need

        // For example
        $keycloakUser['username'] = $keycloakUser['preferred_username'];

        return $keycloakUser;
    }
}