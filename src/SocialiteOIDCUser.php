<?php

namespace colq2\Keycloak;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\User;

class SocialiteOIDCUser extends User
{
    /**
     * The openid connect token
     *
     * @var string
     */
    public $idToken;

    /**
     * The number of seconds the refresh token is valid for
     *
     * @var int
     */
    public $refreshExpiresIn;


    /**
     * Sets the id token
     *
     * @param $token
     * @return \colq2\Keycloak\SocialiteOIDCUser
     */
    public function setIdToken($token)
    {
        $this->idToken = $token;

        return $this;
    }

    /**
     * Sets the number of seconds refresh token is expires in
     *
     * @param int $refreshExpiresIn
     * @return \colq2\Keycloak\SocialiteOIDCUser
     */
    public function setRefreshExpiresIn(int $refreshExpiresIn)
    {
        $this->refreshExpiresIn = $refreshExpiresIn;

        return $this;
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function getId()
    {
        return Arr::get($this->user, 'sub');
    }

    /**
     * Get the nickname / username for the user.
     *
     * @return string
     */
    public function getNickname()
    {
        return Arr::get($this->user, 'preferred_username');
    }

    /**
     * Get the full name of the user.
     *
     * @return string
     */
    public function getName()
    {
        return Arr::get($this->user, 'name');
    }

    /**
     * Get the e-mail address of the user.
     *
     * @return string
     */
    public function getEmail()
    {
        return Arr::get($this->user, 'email');
    }

    /**
     * Get the avatar / image URL for the user.
     *
     * @return string
     */
    public function getAvatar()
    {
        return Arr::get($this->user, 'picture');
    }

    /**
     * Map the given array onto the user's properties.
     * TODO: Maybe this shouldn't be done?
     * We could have inconsistency in our user models
     *
     * @param  array  $attributes
     * @return $this
     */
    public function map(array $attributes)
    {
        parent::map($attributes);

        // Map oidc attribute to socialite user
        // They are retrieve from the user object

        $this->id = Arr::get($this, 'sub');
        $this->nickname = Arr::get($this, 'preferred_username');
        $this->name = Arr::get($this, 'name');
        $this->email = Arr::get($this, 'email');
        $this->avatar = Arr::get($this, 'picture');


        return $this;
    }
}