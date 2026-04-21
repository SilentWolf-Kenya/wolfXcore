<?php

namespace Pterodactyl\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\Factory as ViewFactory;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Models\BotRepo;
use Pterodactyl\Models\BotAllocation;
use Pterodactyl\Models\Plan;
use Pterodactyl\Services\BotRepoService;
use Pterodactyl\Services\ServerProvisioningService;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\User;

class BotRepoController extends Controller
{
    public function __construct(
        protected AlertsMessageBag $alert,
        protected ViewFactory $view,
        protected BotRepoService $botService,
        protected ServerProvisioningService $provisioner,
    ) {
        $this->middleware(function ($request, $next) {
            if (!session('wxn_super')) {
                $this->alert->danger('Access denied. Bot management is restricted to Super Admins only.')->flash();
                return redirect()->route('admin.index');
            }
            return $next($request);
        });
    }

    public function index(): View
    {
        $repos = BotRepo::withCount([
            'allocations',
            'allocations as available_count' => fn($q) => $q->where('status', 'available'),
            'allocations as assigned_count'  => fn($q) => $q->where('status', 'assigned'),
        ])->orderByDesc('created_at')->get();

        return $this->view->make('admin.bots.index', compact('repos'));
    }

    public function view(?BotRepo $repo = null): View
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        if ($repo && $repo->exists) {
            $allocations = $repo->allocations()->orderByDesc('created_at')->get();
            return $this->view->make('admin.bots.view', compact('repo', 'allocations', 'plans'));
        }

        return $this->view->make('admin.bots.view', compact('plans'));
    }

    /**
     * POST /admin/bots/fetch-app-json  (AJAX)
     * Fetch and parse app.json from a GitHub URL.
     */
    public function fetchAppJson(Request $request): JsonResponse
    {
        $request->validate(['repo_url' => 'required|url']);
        try {
            $json   = $this->botService->fetchAppJson($request->repo_url);
            $parsed = $this->botService->parseAppJson($json, $request->repo_url);
            return response()->json(['success' => true, 'data' => $parsed]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /admin/bots  — create a new bot repo
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => 'required|string|max:191',
            'repo_url'   => 'required|url',
            'main_file'  => 'required|string|max:191',
            'git_address'=> 'required|string',
            'is_active'  => 'sometimes|boolean',
        ]);

        $repo = BotRepo::create([
            'name'         => $request->name,
            'description'  => $request->description,
            'image_url'    => $request->image_url,
            'repo_url'     => $request->repo_url,
            'git_address'  => $request->git_address,
            'main_file'    => $request->main_file,
            'env_schema'   => $request->env_schema,
            'app_json_raw' => $request->app_json_raw,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        $this->alert->success("Bot \"{$repo->name}\" created successfully.")->flash();
        return redirect()->route('admin.bots.view', $repo->id);
    }

    /**
     * PATCH /admin/bots/{repo}  — update repo details
     */
    public function update(Request $request, BotRepo $repo): RedirectResponse
    {
        if ($request->input('action') === 'delete') {
            $repo->allocations()->whereNotIn('status', ['assigned'])->delete();
            $repo->delete();
            $this->alert->success('Bot repo deleted.')->flash();
            return redirect()->route('admin.bots');
        }

        if ($request->input('action') === 'refresh') {
            try {
                $json   = $this->botService->fetchAppJson($repo->repo_url);
                $parsed = $this->botService->parseAppJson($json, $repo->repo_url);
                $repo->update($parsed);
                $this->alert->success('app.json refreshed successfully.')->flash();
            } catch (\Exception $e) {
                $this->alert->danger('Refresh failed: ' . $e->getMessage())->flash();
            }
            return redirect()->route('admin.bots.view', $repo->id);
        }

        $request->validate([
            'name'      => 'required|string|max:191',
            'main_file' => 'required|string|max:191',
        ]);

        $repo->update([
            'name'        => $request->name,
            'description' => $request->description,
            'image_url'   => $request->image_url,
            'repo_url'    => $request->repo_url,
            'git_address' => $request->git_address,
            'main_file'   => $request->main_file,
            'env_schema'  => $request->env_schema,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        $this->alert->success("Bot \"{$repo->name}\" updated successfully.")->flash();
        return redirect()->route('admin.bots.view', $repo->id);
    }

    /**
     * POST /admin/bots/{repo}/prepare  — provision N server slots for this repo
     */
    public function prepareSlots(Request $request, BotRepo $repo): RedirectResponse
    {
        $request->validate([
            'slots'   => 'required|integer|min:1|max:50',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $plan      = Plan::findOrFail($request->plan_id);
        $slots     = (int) $request->slots;
        $success   = 0;
        $failed    = 0;

        $dummyUser = User::where('root_admin', true)->first();
        if (!$dummyUser) {
            $this->alert->danger('No admin user found to provision servers under.')->flash();
            return redirect()->route('admin.bots.view', $repo->id);
        }

        for ($i = 0; $i < $slots; $i++) {
            try {
                $server = $this->provisioner->provisionBotSlot($dummyUser, $plan, $repo);
                if ($server) {
                    BotAllocation::create([
                        'repo_id'     => $repo->id,
                        'server_id'   => $server->id,
                        'server_uuid' => $server->uuid,
                        'status'      => 'available',
                        'user_id'     => null,
                    ]);
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error('BotSlot provision failed: ' . $e->getMessage());
                $failed++;
            }
        }

        if ($success > 0) {
            $this->alert->success("{$success} server slot(s) prepared successfully." . ($failed > 0 ? " {$failed} failed." : ''))->flash();
        } else {
            $this->alert->danger("All {$failed} slot(s) failed to provision. Check server logs.")->flash();
        }

        return redirect()->route('admin.bots.view', $repo->id);
    }

    /**
     * DELETE /admin/bots/{repo}/allocations/{allocation}  — remove a slot
     */
    public function removeSlot(BotRepo $repo, BotAllocation $allocation): RedirectResponse
    {
        if ($allocation->status === 'assigned') {
            $this->alert->danger('Cannot remove an assigned slot.')->flash();
        } else {
            $allocation->delete();
            $this->alert->success('Slot removed.')->flash();
        }
        return redirect()->route('admin.bots.view', $repo->id);
    }
}
