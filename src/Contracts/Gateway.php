<?php

namespace colq2\Keycloak\Contracts;

interface Gateway
{


    /**
     * Get the access token response for the given code.
     *
     * @param array $fields
     * @return array
     */
    public function getAccessTokenResponse(array $fields);


    /**
     * Fetch the public key from keycloak server
     *
     * @return string|null
     */
    public function fetchPublicKey();

    /**
     * Get the refresh token response for the given token
     *
     * @param $refreshToken
     * @return array
     */
    public function getRefreshTokenResponse(string $refreshToken);

    /**
     * Get user info from keycloak
     *
     * @param string $accessToken
     * @return array
     */
    public function getUserInfoResponse(string $accessToken);

    /**
     * Returns the base url with realm
     *
     * @return string
     */
    public function getBaseUrlWithRealm();

    /**
     * Return the token url
     *
     * @return string
     */
    public function getTokenUrl();

    /**
     * Return the user info url
     *
     * @return string
     */
    public function getUserInfoUrl();

    /**
     * Return the logout url
     *
     * @return string
     */
    public function getLogoutUrl();

    /**
     * Return the authentication url
     *
     * @return string
     */
    public function getAuthUrl();
}