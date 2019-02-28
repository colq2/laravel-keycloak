<?php

namespace colq2\Keycloak\Contracts;

interface TokenChecker
{
    /**
     * Check if the token is valid
     *
     * @param $token
     * @return bool
     */
    public function checkToken($token): bool;
}