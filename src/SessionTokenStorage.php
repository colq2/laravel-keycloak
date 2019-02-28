<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\TokenStorage;
use Illuminate\Contracts\Session\Session;

class SessionTokenStorage implements TokenStorage
{
    const ACCESS_TOKEN_KEY_NAME = 'keycloak_access_token';

    const REFRESH_TOKEN_KEY_NAME = 'keycloak_refresh_token';

    const ID_TOKEN_KEY_NAME = 'keycloak_id_token';

    /**
     * Session instance
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    private $session;

    /**
     * Create a new SessionTokenStorage
     *
     * @param \Illuminate\Contracts\Session\Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Store token in storage
     *
     * @param string $token
     * @param $key
     */
    public function store($token, $key)
    {
        $this->session->put($key, $token);
    }

    /**
     * Store a an access token
     *
     * @param $token
     */
    public function storeAccessToken($token)
    {
        $this->store($token, self::ACCESS_TOKEN_KEY_NAME);
    }

    /**
     * Store a refresh token
     *
     * @param $token
     */
    public function storeRefreshToken($token)
    {
        $this->store($token, self::REFRESH_TOKEN_KEY_NAME);
    }

    /**
     * Store a id token
     *
     * @param $token
     */
    public function storeIdToken($token)
    {
        $this->store($token, self::ID_TOKEN_KEY_NAME);
    }

    /**
     * Store all tokens
     *
     * @param $accessToken
     * @param $refreshToken
     * @param $idToken
     */
    public function storeAll($accessToken, $refreshToken, $idToken)
    {
        $this->storeAccessToken($accessToken);
        $this->storeRefreshToken($refreshToken);
        $this->storeIdToken($idToken);
    }

    /**
     * Retrieve token from storage
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->session->get($key);
    }

    /**
     * Return access token from storage
     *
     * @return mixed
     */
    public function getAccessToken()
    {
        return $this->get(self::ACCESS_TOKEN_KEY_NAME);
    }

    /**
     * Return refresh token from storage
     *
     * @return mixed
     */
    public function getRefreshToken()
    {
        return $this->get(self::REFRESH_TOKEN_KEY_NAME);
    }

    /**
     * Return id token from storage
     *
     * @return mixed
     */
    public function getIdToken()
    {
        return $this->get(self::ID_TOKEN_KEY_NAME);
    }

    /**
     * Return all tokens from storage in an array
     *
     * @return array
     */
    public function getAllTokens()
    {
        return [
            'access_token' => $this->getAccessToken(),
            'refresh_token' => $this->getRefreshToken(),
            'id_token' => $this->getIdToken(),
        ];
    }

    /**
     * Clear out the storage
     * @param array $tokenKeys
     */
    public function empty(array $tokenKeys = [])
    {
        $keys = array_merge($tokenKeys, [
                self::ACCESS_TOKEN_KEY_NAME,
                self::REFRESH_TOKEN_KEY_NAME,
                self::ID_TOKEN_KEY_NAME,
        ]);

        $this->session->forget(array_unique($keys));
    }
}