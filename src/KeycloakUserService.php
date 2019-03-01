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
        // get socialite user
        $socialiteUser = $this->mapUserArrayToSocialiteUser($this->getUserArrayByToken($token));

        // transform to KeycloakUser
        $user = $this->updateOrCreate($socialiteUser);

        return $user;
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
     * Transform claims to a socialite user
     *
     * @param array $user
     * @return \colq2\Keycloak\SocialiteOIDCUser
     */
    public function mapUserArrayToSocialiteUser(array $user)
    {
        return (new SocialiteOIDCUser)->setRaw($user)
            ->map($user);
    }

    /**
     * @param SocialiteOIDCUser $user
     * @return array
     */
    public function mapSocialiteUserToKeycloakUser(SocialiteOIDCUser $user)
    {
        $keycloakUser = (array) $user;

        // We rename preferred_username to username
        $keycloakUser['username'] = $keycloakUser['preferred_username'];

        return $keycloakUser;
    }

    /**
     * Parse token and get claims as array out of it
     *
     * @param $token
     * @return array
     */
    public function getClaimsFromToken($token): array
    {
        if (! $token instanceof Token) {
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
     * Find a given user provided by Socialite and update itx
     *
     * @param SocialiteOIDCUser $socialiteUser
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function updateOrCreate(SocialiteOIDCUser $socialiteUser): KeycloakUser
    {
        // Update user (maybe the attribute had changed)
        $user = $this->createModel()
            ->newQuery()
            ->updateOrCreate([
                // The unique identifier is the subject, we
                // have to find the user by it
                // TODO: Make this editable
                'sub' => $socialiteUser->getId()
            ], $this->mapSocialiteUserToKeycloakUser($socialiteUser));

        $user->save();

        return $user;
    }

    /**
     * @param SocialiteOIDCUser $socialiteUser
     * @return \colq2\Keycloak\KeycloakUser
     */
    public function findOrCreate(SocialiteOIDCUser $socialiteUser): KeycloakUser
    {
        $user = $this->createModel()
            ->newQuery()
            ->firstOrCreate([
                'sub' => $socialiteUser->getId(),
            ], $this->mapSocialiteUserToKeycloakUser($socialiteUser));

        return $user;
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function createModel()
    {
        $class = '\\'.ltrim($this->model, '\\');

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