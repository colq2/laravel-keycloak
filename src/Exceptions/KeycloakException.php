<?php

namespace colq2\Keycloak\Exceptions;

class KeycloakException extends \Exception
{
    /**
     * Create a new keycloak exception
     *
     * @param string $message
     */
    public function __construct(string $message = "")
    {
        parent::__construct($message);
    }
}