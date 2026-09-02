<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $role = $request->user()?->role;

        $isAdmin = in_array($role?->name, config('system.admin_role_names', []), true);
        $permissions = $role?->permissions ?? [];

        if ($isAdmin || in_array($permission, $permissions, true)) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden. Insufficient permissions.',
        ], 403);
    }
}
