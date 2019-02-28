<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\TokenStorage;

class KeycloakAuthenticator implements Authenticator
{
    /**
     * User Service
     *
     * @var \colq2\Keycloak\KeycloakUserService
     */
    private $keycloakUserService;

    /**
     * @var \colq2\Keycloak\Contracts\TokenStorage
     */
    private $tokenStorage;

    /**
     * Create a new Authentication handler for keycloak authentication.
     *
     * @param \colq2\Keycloak\KeycloakUserService $keycloakUserService
     * @param \colq2\Keycloak\Contracts\TokenStorage $tokenStorage
     */
    public function __construct(KeycloakUserService $keycloakUserService, TokenStorage $tokenStorage)
    {
        $this->keycloakUserService = $keycloakUserService;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Handles redirect request
     *
     * @return mixed
     */
    public function handleRedirect()
    {
        return socialite()
            ->driver('keycloak')
            ->redirect();
    }

    /**
     * Handles callback request
     *
     */
    public function handleCallback()
    {
        // retrieve user from socialite
        // TODO: What happens this doesn't work?
        $socialiteUser = socialite()
            ->driver('keycloak')
            ->user();

        // Find authenticatable user
        $user = $this->keycloakUserService->updateOrCreate($socialiteUser);

        // login user
        $this->authenticateUser($user);

        // We don't need to check the token here, because we
        // we have a guard, which checks the token each request

        // save token to storage
        $this->tokenStorage->storeAll($socialiteUser->token, $socialiteUser->refreshToken, $socialiteUser->idToken);


        // We're done, TODO: fire user logged in event
    }

    public function authenticateUser(KeycloakUser $user)
    {
        auth()
            ->guard('keycloak')
            ->setUser($user);
    }
}