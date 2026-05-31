<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! in_array($request->user()->role, ['admin', 'operator', 'viewer'], true)) {
            auth()->logout();
            abort(403, '账号角色无效');
        }

        return $next($request);
    }
}
