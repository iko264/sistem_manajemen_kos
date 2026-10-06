<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
                $user = $request->user();

        if (! $user) {
            return ApiResponse::error('Silakan login terlebih dahulu', null, 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return ApiResponse::error('Anda tidak berhak mengakses sumber daya ini', null, 403);
        }

        return $next($request);
    }
}
