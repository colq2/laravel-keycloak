<?php

namespace colq2\Keycloak\Contracts;

use colq2\Keycloak\KeycloakUser;

interface UserService
{
    /**
     * Get user from token
     *
     * @param $token
     * @return KeycloakUser
     */
    public function getKeycloakUserByToken($token): KeycloakUser;

    /**
     * Get a user array by a token
     *
     * @param $token
     * @return array
     */
    public function getUserArrayByToken($token);

    /**
     * Transform claims to a socialite user
     *
     * @param array $user
     * @return KeycloakUser
     */
    public function mapUserArrayToKeycloakUser(array $user);

    /**
     * Transform user array
     *
     * @param array $user
     * @return array
     */
    public function mapUser(array $user): array;

    /**
     * Parse token and get claims out of it
     *
     * @param string $token
     * @return array
     */
    public function getClaimsFromToken($token): array;

    /**
     * Find a given user provided by Socialite and update itx
     *
     * @param array $user
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function updateOrCreate(array $user): KeycloakUser;

    /**
     * @param array $user
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function findOrCreate(array $user): KeycloakUser;
}