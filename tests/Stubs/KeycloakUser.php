<?php

namespace colq2\Tests\Keycloak\Stubs;

use colq2\Keycloak\KeycloakUser as Authenticatable;
use Illuminate\Notifications\Notifiable;

class KeycloakUser extends Authenticatable
{
    use Notifiable;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sub',
        'username',
        'name',
        'email',
        'picture'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];
}