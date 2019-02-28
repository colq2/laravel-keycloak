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
    public function checkIdToken($token): bool;

    /**
     * @param $token
     * @return mixed
     */
    public function checkToken($token);
}