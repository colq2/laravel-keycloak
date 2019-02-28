<?php

namespace colq2\Keycloak\Contracts;

interface TokenStorage
{
    /**
     * Store a token in storage
     *
     * @param string $token
     * @param $key
     */
    public function store($token, $key);

    /**
     * Store a an access token
     *
     * @param $token
     */
    public function storeAccessToken($token);

    /**
     * Store a refresh token
     *
     * @param $token
     */
    public function storeRefreshToken($token);

    /**
     * Store a id token
     *
     * @param $token
     */
    public function storeIdToken($token);

    /**
     * Store all tokens
     *
     * @param $accessToken
     * @param $refreshToken
     * @param $idToken
     */
    public function storeAll($accessToken, $refreshToken, $idToken);

    /**
     * Retrieve token from storage
     *
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * Return access token from storage
     *
     * @return mixed
     */
    public function getAccessToken();

    /**
     * Return refresh token from storage
     *
     * @return mixed
     */
    public function getRefreshToken();

    /**
     * Return id token from storage
     * @return mixed
     */
    public function getIdToken();

    /**
     * Return all tokens from storage in an array
     *
     * @return array
     */
    public function getAllTokens();

    /**
     * Clear out the storage
     * @param array $tokenKeys
     */
    public function empty(array $tokenKeys = []);
}