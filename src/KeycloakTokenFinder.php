<?php


namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\TokenFinder;
use colq2\Keycloak\Contracts\TokenStorage as TokenStorage;
use Lcobucci\JWT\Token;

class KeycloakTokenFinder implements TokenFinder
{
    /**
     * @var \colq2\Keycloak\Contracts\TokenStorage
     */
    private $tokenStorage;

    /**
     * Create new RequestTokenParser
     *
     * @param \colq2\Keycloak\Contracts\TokenStorage|null $tokenStorage
     */
    public function __construct(TokenStorage $tokenStorage = null)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Tries to find a token and deliver it back
     *
     * @return \Lcobucci\JWT\Token
     */
    public function findAccessToken()
    {
        // First check the header of the request
        $token = request()->bearerToken();

        // If it's not provided there, let's try to
        // get it from out token storage
        if (empty($token)) {
            $token = $this->tokenStorage->getAccessToken();
        }

        return $token;
    }

    /**
     * Try to find a refresh token and return it
     *
     * @return \Lcobucci\JWT\Token|null
     */
    public function findRefreshToken()
    {
        // 1. Search in our token storage
        $token = $this->tokenStorage->getRefreshToken();

        return $token;
    }

    /**
     * Try to find an id token and return it
     *
     * @return \Lcobucci\JWT\Token|null
     */
    public function findIdToken()
    {
        // 1. Search in our token storage
        $token = $this->tokenStorage->getIdToken();

        // 2. If it is empty, try to retrieve it from userinfo endpoint
        if(empty($token)){
            // TODO
            return null;
        }

        return $token;
    }
}