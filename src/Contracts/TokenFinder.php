<?php

namespace colq2\Keycloak\Contracts;

interface TokenFinder
{
    /**
     * Try to find a token and return it
     *
     * @return \Lcobucci\JWT\Token|null
     */
    public function findAccessToken();

    /**
     * Try to find a refresh token and return it
     *
     * @return \Lcobucci\JWT\Token|null
     */
    public function findRefreshToken();

    /**
     * Try to find an id token and return it
     *
     * @return \Lcobucci\JWT\Token|null
     */
    public function findIdToken();
}