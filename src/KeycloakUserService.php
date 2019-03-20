<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\UserService;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;

class KeycloakUserService implements UserService
{
    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $model;

    /**
     * A list of all attributes defined by the openid connect specification
     *
     * @var array
     */
    protected $oidcAttributes = [
        'sub',
        'name',
        'given_name',
        'family_name',
        'middle_name',
        'nickname',
        'preferred_username',
        'profile',
        'picture',
        'website',
        'email',
        'email_verified',
        'gender',
        'birthday',
        'zoneinfo',
        'locale',
        'phone_number',
        'phone_number_verified',
        'address',
        'updated_at',
    ];

    public function __construct(string $model = KeycloakUser::class)
    {

        $this->model = $model;
    }

    /**
     * Get user out of a token
     *
     * @param $token
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function getKeycloakUserByToken($token): KeycloakUser
    {
        $user = $this->getUserArrayByToken($token);

        return $this->updateOrCreate($user);
    }

    /**
     *
     * @param $token
     * @return array
     */
    public function getUserArrayByToken($token)
    {
        $claims = $this->getClaimsFromToken($token);

        return $this->extractOIDCUserClaims($claims);
    }

    /**
     * Transform claims
     *
     * @param array $user
     * @return KeycloakUser
     */
    public function mapUserArrayToKeycloakUser(array $user)
    {
        $keycloakUser = $this->createModel();
        $keycloakUser->setRawAttributes($user, true);

        return $keycloakUser;
    }

    /**
     * Map user
     *
     * @param array $user
     * @return array
     */
    public function mapUser(array $user): array
    {
        $user['username'] = $user['preferred_username'];

        $user['roles'] = [
            'realm_access' => Arr::get($user, 'realm_access', []),
            'resource_access' => Arr::get($user, 'resource_access', [])
        ];

        return $user;
    }

    /**
     * Parse token and get claims as array out of it
     *
     * @param $token
     * @return array
     */
    public function getClaimsFromToken($token): array
    {
        if (!$token instanceof Token) {
            $token = (new Parser)->parse($token);
        }

        $parsedClaims = $token->getClaims();

        $claims = [];
        foreach ($parsedClaims as $key => $claim) {
            if ($claim instanceof Claim) {
                $claims[$claim->getName()] = $claim->getValue();
            }
        }

        return $claims;
    }

    /**
     * Extract the openid connect user attributes from raw user
     *
     * @param array $claims
     * @return array
     */
    protected function extractOIDCUserClaims(array $claims)
    {
        $user = [];

        foreach ($this->oidcAttributes as $attribute) {
            $user[$attribute] = Arr::get($claims, $attribute);
        }

        return $user;
    }

    /**
     * Find a given user and update it
     *
     * @param array $user
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function updateOrCreate(array $user): KeycloakUser
    {
        // Update user (maybe the attribute had changed)
        $keycloakUser = $this->createModel()
            ->newQuery()
            ->updateOrCreate([
                // The unique identifier is the subject, we
                // have to find the user by it
                // TODO: Make this editable
                'sub' => $user['sub']
            ], $this->mapUser($user));

        $keycloakUser->save();

        return $keycloakUser;
    }

    /**
     * @param array $user
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function findOrCreate(array $user): KeycloakUser
    {
        $keycloakUser = $this->createModel()
            ->newQuery()
            ->firstOrCreate([
                'sub' => $user['sub'],
            ], $this->mapUser($user));

        return $keycloakUser;
    }

    /**
     * Create a new instance of the model.
     *
     * @return KeycloakUser Model
     */
    protected function createModel()
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Gets the name of the Eloquent user model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the name of the Eloquent user model.
     *
     * @param  string $model
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }
}