# IMPORTANT
This is going to be an openid-connect client for laravel to login with keycloak.

This is still in development and not ready for production.

Feel free to contribute to this.

# To do
[ ] Allow different user storage's like Cache, Session, Eloquent, Database etc.

[ ] Write Readme

[ ] Upload to packagist

# Installation
`composer require colq2/laravel-keycloak`

Publish config and migrations

`php artisan vendor:publish --provider=colq2\Keycloak\KeycloakServiceProvider`

This project redefines the user model and migrations. There is no need for password reset table. Furthermore we need another properties on the user:
* id
* sub
* username
* name
* email
* picture

You can delete all migrations in your laravel project if you want to use keycloak as the only auth possibility.


Add following to your .env file:

```
KEYCLOAK_USER_MODEL=
KEYCLOAK_BASE_URL=
KEYCLOAK_REALM=
KEYCLOAK_CLIENT_ID=
KEYCLOAK_CLIENT_SECRET=
KEYCLOAK_REDIRECT=/callback
```

## Usage

Controller:

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

Routes:

```
Route::get('login', 'LoginController@handleRedirect');
Route::get('callback', 'LoginController@handleCallback');
```


## Middleware
This package comes with `CheckRealmAccess` and `CheckResourceAccess` middleware. You can add them in the `app/Http/Kernel.php` file.

```
protected $routeMiddleware = [
    // ...
    'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
    'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
    'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
];
```

Then you can use the middleware:

### Realm Access Middleware

```
Route::get('post/{post}', function(Post $post) {
    // The current user has role1 and role2 in the realm
})->middleware('realm_access:role1,role2');
```

### Resource Access Middleware
```
Route::get('post/{post}', function(Post $post) {
    // The current user has role1 and role2 in client1 in the realm
})->middleware('resource_access:client1,role1,role2');
```

## Custom User

The custom keycloak user saves the properties: id, sub, username, name, email and picture

To customize this you should do three things:

1. Update migration
2. Update User model
3. Provide a custom Service

### Update migrations

In the create_user_table migration you can add or remove your needed properties.
By default you can use all claims that are defined in the openid-connect specs.

```
 public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sub')->unique();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('picture')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
```

### Update User Model
After you defined which properties you need. You have to define the same in the fillable attribute.


```
<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use colq2\Keycloak\KeycloakUser as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

 	/**
     * @var string 
     */
    protected $table = 'users';

    /**
     *
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // TODO: Update the fillable 
        'sub, 'username', 'name', 'email', 'picture'
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

### Provide a custom user service

The openid-connect claims have to be parsed to your custom properties. Just build a new class and extend the default User service.
Then update the app binding to your user service.

In CustomUserService:

```
use colq2\Keycloak\KeycloakUserService;
use colq2\Keycloak\SocialiteOIDCUser;

class CustomUserService extends KeycloakUserService
{

    public function mapSocialiteUserToKeycloakUser(SocialiteOIDCUser $user)
    {
        $keycloakUser = (array) $user;

        // DO whatever you need

        // For example
        $keycloakUser['username'] = $keycloakUser['preferred_username'];

        return $keycloakUser;
    }
}
```

And bind this to your application in AppServiceProvider:

```
**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(UserService::class, function($app){
            return new CustomUserService($app['config']->get('keycloak.model'));
        });
    }
```