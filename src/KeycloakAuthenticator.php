<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\Contracts\UserService;

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
     * Scopes that should be added used
     *
     * @var array $scopes
     */
    protected $scopes = [];

    /**
     * Create a new Authentication handler for keycloak authentication.
     *
     * @param UserService $keycloakUserService
     * @param \colq2\Keycloak\Contracts\TokenStorage $tokenStorage
     */
    public function __construct(UserService $keycloakUserService, TokenStorage $tokenStorage)
    {
        $this->keycloakUserService = $keycloakUserService;
        $this->tokenStorage = $tokenStorage;
    }



    /**
     * Scopes which should be added
     *
     * @param array $scopes
     * @return KeycloakAuthenticator
     *
     */
    public function withScopes(array $scopes)
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * Handles redirect request
     *
     * @return RedirectRes
     */
    public function handleRedirect()
    {
        return socialite()
            ->driver('keycloak')
            ->setScopes($this->scopes)
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

    /**
     * Authenticate a specific user
     *
     * @param KeycloakUser $user
     * @return mixed|void
     */
    public function authenticateUser(KeycloakUser $user)
    {
        auth()
            ->guard('keycloak')
            ->setUser($user);
    }

    /**
     * Returns the additional scopes
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

}