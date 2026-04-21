<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->session()->get('wxn_super')) {
            return redirect()->route('admin.super.auth')
                ->with('error', 'Super Admin authentication required.');
        }

        return $next($request);
    }
}
