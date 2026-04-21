<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\Plan;
use Pterodactyl\Http\Controllers\Controller;

class WxnPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->whereRaw('UPPER(name) != ?', ['ADMIN PANEL'])
            ->orderBy('price')
            ->get()
            ->map(function (Plan $plan) {
                return [
                    'id'    => $plan->id,
                    'name'  => strtoupper($plan->name),
                    'price' => (int) $plan->price,
                    'ram'   => $plan->memory == 0 ? '∞' : ($plan->memory >= 1024 ? round($plan->memory / 1024, 1) . ' GB' : $plan->memory . ' MB'),
                    'cpu'   => $plan->cpu == 0 ? '∞' : $plan->cpu . '%',
                    'disk'  => $plan->disk == 0 ? '∞' : ($plan->disk >= 1024 ? round($plan->disk / 1024, 1) . ' GB' : $plan->disk . ' MB'),
                    'dbs'   => $plan->databases,
                    'badge' => $plan->is_featured ? 'MOST POPULAR' : null,
                ];
            });

        return response()
            ->json(['data' => $plans])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
