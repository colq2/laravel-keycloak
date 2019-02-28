# IMPORTANT
This is going to be an openid-connect client for laravel to login with keycloak.

This is still in development and not ready for production.

Feel free to contribute to this.

# To do
[ ] Allow different user storage's like Cache, Session, Eloquent, Database etc.

[ ] Save session state

[ ] Implement nonce

[ ] Write Readme

# Installation
`composer require colq2/laravel-keycloak`

## Usage

Write in a Controller:

```
<?php

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
        $this->handleCallback();

        $user = auth()->user();
    }
}
```

Add routes:
```
Route::get('login', 'LoginController@handleRedirect');
Route::get('callback', 'LoginController@handleCallback');
```

## Update migrations
This project redefines the user model and migrations. There is no need for password reset table. Furthermore we need another properties on the user:
* id
* username
* name
* email
* picture

You can delete all migrations in your laravel project if you want to use keycloak as the only auth possibility.
You could publish our user migration with
``php artisan vendor:publish``
or create your own migration and User.

## Update User Model
This packages provides a User Model which is quite similar to the provided model by laravel. However, it removes unused dependencies.
Put the following Code into the User Class in your project.
Else you can also update this class to your own needs.

```
<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use colq2\Keycloak\User as Authenticatable;

class User extends Authenticatable
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
        'username', 'name', 'email', 'avatar'
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

```