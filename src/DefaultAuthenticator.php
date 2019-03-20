<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Authenticator;
use colq2\Keycloak\Contracts\TokenStorage;
use colq2\Keycloak\Contracts\UserService;
use colq2\Keycloak\Exceptions\InvalidStateException;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak as KeycloakProvider;

class DefaultAuthenticator implements Authenticator
{
    const STATE_KEY = 'keycloak_oauth2state';

    /**
     * User Service
     *
     * @var \colq2\Keycloak\KeycloakUserService
     */
    private $keycloakUserService;

    /**
     * @var \colq2\Keycloak\Contracts\TokenStorage
     */
    private $tokenStorage;

    /**
     * @var KeycloakProvider $provider
     */
    protected $provider;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * Scopes that should be added used
     *
     * @var array $scopes
     */
    protected $scopes = [];

    /**
     * @var Request
     */
    protected $request;

    /**
     * Create a new Authentication handler for keycloak authentication.
     *
     * @param UserService $keycloakUserService
     * @param \colq2\Keycloak\Contracts\TokenStorage $tokenStorage
     * @param KeycloakProvider $provider
     * @param Session $session
     * @param Request $request
     */
    public function __construct(UserService $keycloakUserService, TokenStorage $tokenStorage, KeycloakProvider $provider, Session $session, Request $request)
    {
        $this->keycloakUserService = $keycloakUserService;
        $this->tokenStorage = $tokenStorage;
        $this->provider = $provider;
        $this->session = $session;
        $this->request = $request;
    }


    /**
     * Scopes which should be added
     *
     * @param array $scopes
     * @return DefaultAuthenticator
     *
     */
    public function withScopes(array $scopes)
    {
        $this->scopes = $scopes;

        return $this;
    }

    /**
     * Handles redirect request
     *
     * @return RedirectResponse
     */
    public function handleRedirect()
    {
        $authUrl = $this->provider->getAuthorizationUrl(
            ['scope' => implode(' ', $this->scopes)]
        );
        $state = $this->provider->getState();
        $this->session->put(self::STATE_KEY, $state);

        return RedirectResponse::create($authUrl);
    }

    /**
     * Handles callback request
     *
     * @throws \Stevenmaguire\OAuth2\Client\Provider\Exception\EncryptionConfigurationException
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function handleCallback()
    {
        // retrieve user from socialite
        // TODO: What happens this doesn't work?
        if ($this->hasInvalidState()) {
            throw new InvalidStateException();
        }

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $this->request->get('code')
        ]);

        $user = $this->provider->getResourceOwner($token);

        // Find authenticatable user
        $userArray = $user->toArray();
        $user = $this->keycloakUserService->updateOrCreate($userArray);

        // login user
        $this->authenticateUser($user);

        // save token to storage
        $this->tokenStorage->storeAccessToken($token->getToken());
        $this->tokenStorage->storeRefreshToken($token->getRefreshToken());
        $this->tokenStorage->store($token, 'oauth2_token');

        // We're done, TODO: fire user logged in event
    }

    /**
     * Authenticate a specific user
     *
     * @param KeycloakUser $user
     * @return mixed|void
     */
    public function authenticateUser(KeycloakUser $user)
    {
        auth()
            ->guard('keycloak')
            ->setUser($user);
    }

    /**
     * Returns the additional scopes
     *
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     *
     * Determine if the current request / session has a mismatching "state".
     *
     * @return bool
     */
    protected function hasInvalidState()
    {
        $state = $this->request->session()->pull(self::STATE_KEY);
        return !(strlen($state) > 0 && $this->request->input('state') === $state);
    }

}