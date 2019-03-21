# IMPORTANT
This is going to be an easy-to-use keycloak adapter for laravel.

This is still in development and not ready for production.

Feel free to contribute to this.

# To do
[ ] Allow different user storage's like Cache, Session, Eloquent, Database etc.

# Installation
`composer require colq2/laravel-keycloak`

Publish config and migrations

`php artisan vendor:publish --provider=colq2\Keycloak\KeycloakServiceProvider`

This project redefines the user model and migrations. There is no need for password reset table. Furthermore we need another properties on the user (This are mostly the openid-connect defined properties):
* id
* sub
* username
* name
* email
* picture
* roles
 
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
    'realm_access' => \colq2\Keycloak\Http\Middleware\CheckRealmAccess::class,
    'resource_access' => \colq2\Keycloak\Http\Middleware\CheckResourceAccess::class,
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
            $table->json('roles')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
```

### Update User Model
After you defined which properties you need. You have to define the same in the fillable attribute.


```
<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Roles\HasRoles;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class KeycloakUser extends Model implements AuthenticatableContract, AuthorizableContract, HasRoles
{
    use Authenticatable, Authorizable;

    protected $table = 'users';

    protected $fillable = [ 'sub', 'username', 'name', 'email', 'picture', 'roles' ];

    protected $casts = [ 'roles' => 'array' ];

    protected $hidden = [ 'remember_token' ];

    public function getAllRoles(): array
    {
        return $this->roles;
    }
}

```

### Provide a custom user service

The openid-connect claims have to be parsed to your custom properties. Just build a new class and extend the default User service.
Then update the app binding to your user service.

In CustomUserService:

```
<?php

namespace colq2\Keycloak\Examples;

use colq2\Keycloak\KeycloakUserService;

class CustomUserService extends KeycloakUserService
{

    /**
     * @param array $user
     * @return array|\colq2\Keycloak\KeycloakUser
     */
    public function mapUser(array $user): array
    {
        // Do whatever you need
        $user['username'] = $user['preferred_username'];

        // And return it
        return $user;
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
