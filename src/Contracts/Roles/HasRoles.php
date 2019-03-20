<?php

namespace colq2\Keycloak\Contracts\Roles;


interface HasRoles
{
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
    public function getAllRoles(): array;
}