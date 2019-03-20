<?php

namespace colq2\Keycloak\Roles;


use colq2\Keycloak\Contracts\Roles\HasRoles;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token as JWTToken;

class Token implements HasRoles
{

    /**
     * @var \Lcobucci\JWT\Token $token
     */
    protected $token;

    public function __construct($token)
    {
        if (!$token instanceof JWTToken) {
            $token = (new Parser())->parse($token);
        }

        $this->token = $token;

    }

    /**
     * Returns all roles in an assoc array in form of
     * [
     *  realm_access => [roles => [ ... ] ]
     *
     *  resource_access => [
     *      client => [ roles [ ... ] ]
     *  ]
     * ]
     *
     * @return array
     */
    public function getAllRoles(): array
    {
        $roles = [
            'realm_access' => (array)$this->token->getClaim('realm_access', []),
            'resource_access' => (array)$this->token->getClaim('resource_access', [])
        ];

        return $roles;
    }
}