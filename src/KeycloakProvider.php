<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\Contracts\UserService;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\ProviderInterface;

/**
 * Class KeycloakProvider
 * A socialite provider to retrieve a user from keycloak identity management
 *
 * @package App
 */
class KeycloakProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Indicates if the session state should be utilized.
     *
     * @var bool
     */
    protected $stateless = false;


    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid'];

    /**
     * UserService instance
     *
     * @var UserService $userService
     */
    private $userService;

    /**
     * @var Gateway $gateway
     */
    private $gateway;

    /**
     * Get the authentication URL for the provider.
     *
     * @param  string $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase($this->getGateway()->getBaseUrlWithRealm() . '/protocol/openid-connect/auth', $state);
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl()
    {
        return $this->getGateway()->getTokenUrl();
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        return $this->getUserService()->getUserArrayByToken($token);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param  array $user
     * @return \colq2\Keycloak\SocialiteOIDCUser
     */
    protected function mapUserToObject(array $user)
    {
        return $this->getUserService()->mapUserArrayToSocialiteUser($user);
    }

    /**
     * {@inheritdoc}
     * @throws \colq2\Keycloak\OpenIDConnectTokenNotPresent
     */
    public function user()
    {
        if ($this->hasInvalidState()) {
            throw new InvalidStateException;
        }

        $response = $this->getAccessTokenResponse($this->getCode());

        // Check if id token is present
        if (!array_key_exists('id_token', $response)) {
            throw new OpenIDConnectTokenNotPresent();
        }

        // Map user to object
        $user = $this->mapUserToObject(
            $this->getUserByToken($token = Arr::get($response, 'id_token'))
        );

        // set tokens on the user
        return $user->setToken($token)
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'))
            ->setRefreshExpiresIn(Arr::get($response, 'refresh_expires_in'))
            ->setIdToken(Arr::get($response, 'id_token'));
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        return $this->getGateway()->getAccessTokenResponse($this->getTokenFields($code));
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param  string $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUrl,
            'grant_type' => 'authorization_code',
        ];
    }

    /**
     * @return \colq2\Keycloak\Contracts\UserService
     */
    public function getUserService(): UserService
    {
        if (is_null($this->userService)) {
            $this->userService = app()->make($this->userService);
        }
        return $this->userService;
    }

    /**
     * @param \colq2\Keycloak\Contracts\UserService $userService
     * @return \colq2\Keycloak\KeycloakProvider
     */
    public function setUserService(UserService $userService): KeycloakProvider
    {
        $this->userService = $userService;

        return $this;
    }

    /**
     * Set the scopes of the requested access.
     *
     * @param  array|string $scopes
     * @return \Laravel\Socialite\Two\AbstractProvider
     */
    public function setScopes($scopes)
    {
        $scopes[] = 'openid';

        return parent::setScopes($scopes);
    }

    /**
     * Returns a gateway instance
     *
     * @return Gateway|mixed
     */
    protected function getGateway()
    {
        if (!$this->gateway) {
            $this->gateway = app()->make(Gateway::class);
        }

        return $this->gateway;
    }
}