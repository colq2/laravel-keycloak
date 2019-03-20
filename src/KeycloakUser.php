<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Roles\HasRoles;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class KeycloakUser extends Model implements AuthenticatableContract, AuthorizableContract, HasRoles
{
    use Authenticatable, Authorizable;

    /**
     * Manual define the table
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sub', 'username', 'name', 'email', 'picture', 'roles'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'roles' => 'array'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

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
        return $this->roles;
    }
}