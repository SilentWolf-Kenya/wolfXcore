<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Pterodactyl\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\View\Factory as ViewFactory;
use Pterodactyl\Http\Controllers\Controller;

class PlanController extends Controller
{
    public function __construct(
        protected AlertsMessageBag $alert,
        protected ViewFactory $view,
    ) {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || !$user->root_admin) {
                $this->alert->danger('Access denied. Plans management requires administrator access.')->flash();
                return redirect()->route('admin.index');
            }
            return $next($request);
        });
    }

    public function index(): View
    {
        return $this->view->make('admin.plans.index', [
            'plans' => Plan::orderBy('price')->get(),
        ]);
    }

    public function view(Plan $plan): View
    {
        return $this->view->make('admin.plans.view', compact('plan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:191',
            'description' => 'nullable|string|max:500',
            'price'       => 'required|numeric|min:0',
            'memory'      => 'required|integer|min:0',
            'cpu'         => 'required|integer|min:0|max:10000',
            'disk'        => 'required|integer|min:0',
            'io'          => 'required|integer|min:10|max:1000',
            'databases'   => 'required|integer|min:0',
            'backups'     => 'required|integer|min:0',
            'is_featured' => 'sometimes|boolean',
            'is_active'   => 'sometimes|boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active']   = $request->boolean('is_active');

        $plan = Plan::create($validated);

        $this->alert->success("Plan \"{$plan->name}\" was created successfully.")->flash();

        return redirect()->route('admin.plans.view', $plan->id);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        if ($request->input('action') === 'delete') {
            $name = $plan->name;
            $plan->delete();
            $this->alert->success("Plan \"{$name}\" was deleted successfully.")->flash();
            return redirect()->route('admin.plans');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:191',
            'description' => 'nullable|string|max:500',
            'price'       => 'required|numeric|min:0',
            'memory'      => 'required|integer|min:0',
            'cpu'         => 'required|integer|min:0|max:10000',
            'disk'        => 'required|integer|min:0',
            'io'          => 'required|integer|min:10|max:1000',
            'databases'   => 'required|integer|min:0',
            'backups'     => 'required|integer|min:0',
            'is_featured' => 'sometimes|boolean',
            'is_active'   => 'sometimes|boolean',
        ]);

        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active']   = $request->boolean('is_active');

        $plan->update($validated);

        $this->alert->success("Plan \"{$plan->name}\" was updated successfully.")->flash();

        return redirect()->route('admin.plans.view', $plan->id);
    }
}
