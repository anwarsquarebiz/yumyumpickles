<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Gate the admin panel to staff accounts.
     *
     * Per-model policies still apply on top of this; the middleware only keeps
     * customers out of the /admin surface entirely.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdmin()) {
            abort(403, 'This area is restricted to store staff.');
        }

        return $next($request);
    }
}
