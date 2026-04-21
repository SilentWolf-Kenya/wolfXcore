<?php

namespace Pterodactyl\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Pterodactyl\Models\Plan;

class LandingController extends Controller
{
    public function __construct(private AuthFactory $auth)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($this->auth->guard()->check()) {
            return redirect('/dashboard');
        }

        $plans = Plan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('landing', ['plans' => $plans]);
    }
}
