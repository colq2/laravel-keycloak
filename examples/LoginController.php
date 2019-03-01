<?php

namespace colq2\Keycloak\Examples;

class LoginController extends \Illuminate\Routing\Controller
{
    /**
     * @var \colq2\Keycloak\Contracts\Authenticator
     */
    private $authenticator;

    public function __construct(\colq2\Keycloak\Contracts\Authenticator $authenticator)
    {

        $this->authenticator = $authenticator;
    }

    public function handleRedirect()
    {
        $this->authenticator->handleRedirect();
    }

    public function handleCallback()
    {
        $this->authenticator->handleCallback();

        $user = auth()->user();
    }
}