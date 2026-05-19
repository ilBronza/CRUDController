<?php

namespace IlBronza\CRUD\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class CRUDBasePackageMiddlewareRolesPermissions
{
    /**
     * The config root key used by the package.
     *
     * Example: "vehicles", "orders", "warehouse".
     */
    protected string $configPackageName;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403);

        $roles = $this->getRolesForCurrentRoute($request);

        if (! count($roles)) {
            abort(403, 'No roles configured for this route.');
        }

        if (! $this->userHasAnyConfiguredRole($user, $roles)) {
            abort(403, 'User does not have the right roles: ' . json_encode($roles));
        }

        return $next($request);
    }

    /**
     * Get the roles assigned to the current route.
     */
    protected function getRolesForCurrentRoute(Request $request): array
    {
        $routeName = $request->route()?->getName();

        $roles = [];

        if ($routeName) {
            $roles = config($this->getRouteRolesConfigKey($routeName));
        }

        if (! $roles) {
            $roles = config($this->getDefaultRolesConfigKey(), []);
        }

        return $this->normalizeRoles($roles);
    }

    /**
     * Build the config key for route-specific roles.
     */
    protected function getRouteRolesConfigKey(string $routeName): string
    {
        return "{$this->configPackageName}.routeRoles.{$routeName}";
    }

    /**
     * Build the config key for default roles.
     */
    protected function getDefaultRolesConfigKey(): string
    {
        return "{$this->configPackageName}.defaultRoles";
    }

    /**
     * Normalize roles into a clean array.
     */
    protected function normalizeRoles(array|string|null $roles): array
    {
        if (is_string($roles)) {
            $roles = explode('|', $roles);
        }

        if (! is_array($roles)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $role) => trim($role),
            $roles
        )));
    }

    /**
     * Check whether the user has at least one allowed role.
     */
    protected function userHasAnyConfiguredRole(mixed $user, array $roles): bool
    {
        if (! method_exists($user, 'hasAnyRole')) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }
}