<?php

namespace Pterodactyl\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pterodactyl\Models\Setting;

class SiteMaintenanceMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $maintenance = Setting::where('key', 'settings::app:maintenance')->value('value') ?? '0';

        if ($maintenance !== '1') {
            return $next($request);
        }

        // Always allow the login page so admins can authenticate
        if ($request->is('auth') || $request->is('auth/*')) {
            return $next($request);
        }

        // Always allow the super admin auth route (to turn maintenance off)
        if ($request->is('admin/wxn-super/auth') || $request->is('admin/wxn-super/auth/*')) {
            return $next($request);
        }

        // Let root admins through everything
        if ($request->user() && $request->user()->root_admin) {
            return $next($request);
        }

        return response()->view('errors.site-maintenance', [], 503);
    }
}
