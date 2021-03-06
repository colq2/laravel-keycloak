<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\Contracts\TokenChecker;
use colq2\Keycloak\Contracts\TokenFinder;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\Contracts\UserService;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;

class KeycloakGuard implements Guard
{
    use GuardHelpers;

    const KEYCLOAK_TOKEN_NAME = 'keycloak_token';

    /**
     * @var \colq2\Keycloak\KeycloakTokenFinder $tokenFinder
     */
    private $tokenFinder;

    /**
     * @var \colq2\Keycloak\Contracts\TokenStorage $tokenStorage
     */
    private $tokenStorage;

    /**
     * @var \colq2\Keycloak\Contracts\UserService $userService
     */
    private $userService;

    /**
     * @var Gateway
     */
    private $gateway;
    /**
     * @var TokenChecker
     */
    private $tokenChecker;

    public function __construct(
        UserProvider $provider,
        TokenStorage $tokenStorage,
        TokenChecker $tokenChecker,
        TokenFinder $tokenFinder,
        UserService $userService,
        Gateway $gateway
    )
    {

        $this->provider = $provider;
        $this->tokenFinder = $tokenFinder;
        $this->tokenStorage = $tokenStorage;
        $this->tokenChecker = $tokenChecker;
        $this->userService = $userService;
        $this->gateway = $gateway;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        // If we've already retrieved the user for the current request we can just
        // return it back immediately. We do not want to fetch the user data on
        // every call to this method because that would be tremendously slow.
        if (!is_null($this->user)) {
            return $this->user;
        }

        $user = null;

        // 1. Find Access Token
        $accessToken = $this->tokenFinder->findAccessToken();

        // 2. Check if the access token is valid
        if (!$this->tokenChecker->checkToken($accessToken)) {
            // 3. If it's not, try to refresh it
            if (!$this->refreshToken()) {
                // 4. If this fails we quit
                $this->tokenStorage->empty();

                return null;
            }

            // Tokens are refreshed, we need to pull the access token
            $accessToken = $this->tokenFinder->findAccessToken();
        }

        // 5. If the access token is valid, try to find an id token
        $idToken = $this->tokenFinder->findIdToken();

        // 6. If an id token was found, get a user by this token
        // 7. Else get a user by the access token
        if (empty($idToken)) {
            // TODO: Split into get User by decoded token and get User from UserEndpoint
            $user = $this->userService->getKeycloakUserByToken($accessToken);
        } else {
            if (!$this->tokenChecker->checkIdToken($idToken)) {
                $this->tokenStorage->empty();
            }

            $user = $this->userService->getKeycloakUserByToken($idToken);
        }


        // 8. Return the  user
        return $this->user = $user;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (array_key_exists('id_token', $credentials)) {
            if (!$this->tokenChecker->checkIdToken($credentials['id_token'])) {
                return false;
            }
        }

        if (array_key_exists('token', $credentials)) {
            if (!$this->tokenChecker->checkToken($credentials['token'])) {
                return false;
            }
        }

        if (array_key_exists('access_token', $credentials)) {
            if (!$this->tokenChecker->checkToken($credentials['access_token'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the current user.
     *
     * @param  Authenticatable $user
     * @return $this
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Try to refresh tokens
     *
     * @return bool
     */
    private function refreshToken()
    {
        $refreshToken = $this->tokenFinder->findRefreshToken();

        if (empty($refreshToken)) {
            return false;
        }

        $tokens = $this->gateway->getRefreshTokenResponse($refreshToken);

        $this->tokenStorage->storeAccessToken($tokens['access_token']);
        $this->tokenStorage->storeRefreshToken($tokens['refresh_token']);

        return true;
    }

}