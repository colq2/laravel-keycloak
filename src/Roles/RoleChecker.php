<?php

namespace colq2\Keycloak\Roles;


use colq2\Keycloak\Contracts\Roles\HasRoles;
use Illuminate\Support\Arr;

class RoleChecker
{

    /**H
     * @var HasRoles $subject
     */
    protected $subject;

    public function for(HasRoles $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @param $roles
     * @return bool
     */
    public function hasRealmAccessRole($roles)
    {
        $r = $this->subject->getAllRoles();
        $realmRoles = Arr::get($this->subject->getAllRoles(), 'realm_access.roles', []);

        if (!is_array($roles)) {
            $roles = (array)$roles;
        }

        foreach ($roles as $role) {
            if (!in_array($role, $realmRoles)) {
                return false;
            }
        }

        return true;
    }

    public function hasResourceAccessRole(string $client, $roles)
    {
        $resourceRoles = Arr::get($this->subject->getAllRoles(), 'resource_access.' . $client . '.roles', []);

        if (!is_array($roles)) {
            $roles = (array)$roles;
        }

        foreach ($roles as $role) {
            if (!in_array($role, $resourceRoles)) {
                return false;
            }
        }

        return true;
    }
}