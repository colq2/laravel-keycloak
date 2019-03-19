<?php

namespace colq2\Keycloak\Http\Middleware;


use Closure;
use colq2\Keycloak\Roles\RoleChecker;
use Illuminate\Validation\UnauthorizedException;

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
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (auth()->guest()) {
            throw new UnauthorizedException('Please log in.');
        }

        $user = auth()->user();

        $hasAccess = $this->roleChecker->for($user)->hasRealmAccessRole($roles);

        if (!$hasAccess) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}