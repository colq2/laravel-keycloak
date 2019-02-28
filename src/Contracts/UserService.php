<?php

namespace colq2\Keycloak\Contracts;

use colq2\Keycloak\KeycloakUser;
use colq2\Keycloak\SocialiteOIDCUser;
use Laravel\Socialite\Two\User as SocialiteUser;

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
     * @return \colq2\Keycloak\SocialiteOIDCUser
     */
    public function mapUserArrayToSocialiteUser(array $user);

    /**
     * Transform socialite user to a keycloak user
     *
     * @param SocialiteOIDCUser $user
     * @return array
     */
    public function mapSocialiteUserToKeycloakUser(SocialiteOIDCUser $user);


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
     * @param SocialiteOIDCUser $socialiteUser
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function updateOrCreate(SocialiteOIDCUser $socialiteUser): KeycloakUser;

    /**
     * @param SocialiteOIDCUser $socialiteUser
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function findOrCreate(SocialiteOIDCUser $socialiteUser): KeycloakUser;
}