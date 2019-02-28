<?php

namespace colq2\Keycloak\Contracts;

use colq2\Keycloak\KeycloakUser;

interface Authenticator
{
    /**
     * Handles the redirect request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleRedirect();

    /**
     * Handles the callback
     * @return mixed
     */
    public function handleCallback();

    /**
     * Authenticate a keycloak user
     *
     * @param \colq2\Keycloak\KeycloakUser $user
     * @return mixed
     */
    public function authenticateUser(KeycloakUser $user);
}