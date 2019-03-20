<?php

namespace colq2\Keycloak\Examples;

use colq2\Keycloak\KeycloakUserService;

class CustomUserService extends KeycloakUserService
{

    /**
     * @param array $user
     * @return array|\colq2\Keycloak\KeycloakUser
     */
    public function mapUser(array $user): array
    {
        // Do whatever you need
        $user['username'] = $user['preferred_username'];

        // And return it
        return $user;
    }
}