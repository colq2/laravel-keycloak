<?php

namespace colq2\Tests\Keycloak\Stubs\Http\Controllers;


use colq2\Keycloak\Contracts\Authenticator;

class LoginController
{


    /**
     * @var Authenticator
     */
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {

        $this->authenticator = $authenticator;
    }

    public function handleRedirect()
    {
        return  $this->authenticator->withScopes(['profile', 'roles'])->handleRedirect();
    }

    public function handleCallback()
    {
        return $this->authenticator->handleCallback();
    }
}