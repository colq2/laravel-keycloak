<?php

namespace colq2\Keycloak\Http\Middleware;


use Closure;
use colq2\Keycloak\Roles\RoleChecker;
use Illuminate\Auth\AuthenticationException;

class CheckRealmAccess
{
    /**
     * @var RoleChecker
     */
    private $roleChecker;

    /**
     * CheckRole constructor.
     * @param RoleChecker $roleChecker
     */
    public function __construct(RoleChecker $roleChecker)
    {

        $this->roleChecker = $roleChecker;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param array $roles
     * @return mixed
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (auth()->guest()) {
            throw new AuthenticationException();
        }

        if (empty($roles)) {
            abort(403, 'Access denied.');
        }

        $user = auth()->user();

        $hasAccess = $this->roleChecker->for($user)->hasRealmAccessRole($roles);

        if (!$hasAccess) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}