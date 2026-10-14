<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Membatasi akses hanya untuk user dengan salah satu role yang diberikan,
     * mis. `role:admin` atau `role:admin,approver`.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        abort_unless($user instanceof User && in_array($user->role, $allowed, true), 403);

        return $next($request);
    }
}
