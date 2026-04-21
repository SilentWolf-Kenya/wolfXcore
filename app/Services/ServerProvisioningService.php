<?php

namespace Pterodactyl\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Egg;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Allocation;
use Pterodactyl\Models\Plan;
use Pterodactyl\Models\Server;
use Pterodactyl\Models\BotRepo;
use Pterodactyl\Services\Servers\ServerCreationService;
use Pterodactyl\Exceptions\DisplayException;

class ServerProvisioningService
{
    public function __construct(private ServerCreationService $creator)
    {
    }

    /**
     * Auto-provision a wolfXcore server for a user after plan purchase.
     * Returns the created Server model, or null on any failure.
     */
    public function provision(User $user, Plan $plan, ?string $serverName = null): ?Server
    {
        try {
            $config = DB::table('wxn_server_config')->first();
            if (!$config) {
                Log::warning('WolfXcore provisioning: wxn_server_config table is empty.');
                return null;
            }

            $egg = Egg::with('variables')->find($config->egg_id);
            if (!$egg) {
                Log::warning('WolfXcore provisioning: egg_id ' . $config->egg_id . ' not found.');
                return null;
            }

            // Lock the allocation row to prevent race conditions on concurrent deploys
            $allocation = Allocation::where('node_id', $config->node_id)
                ->whereNull('server_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$allocation) {
                Log::warning('WolfXcore provisioning: no free allocations on node ' . $config->node_id);
                return null;
            }

            $environment = [];
            foreach ($egg->variables as $variable) {
                $environment[$variable->env_variable] = $variable->default_value ?? '';
            }

            // AUTO_UPDATE=0 by default — running `git pull` on every restart
            // caused OOMs and pulled broken upstream changes. Mirror the same
            // safety default that provisionBotSlot() sets.
            $environment['AUTO_UPDATE'] = '0';

            $dockerImages = $egg->docker_images ?? [];
            if (!empty($config->docker_image_override)) {
                $dockerImage = $config->docker_image_override;
            } elseif (is_array($dockerImages) && !empty($dockerImages)) {
                $dockerImage = array_values($dockerImages)[0];
            } else {
                $dockerImage = $egg->docker_image ?? 'ghcr.io/wolfxcore/yolks:nodejs_18';
            }

            $startup = !empty($config->startup_override) ? $config->startup_override : ($egg->startup ?? 'node index.js');

            if ($serverName && trim($serverName) !== '') {
                $finalName = trim($serverName);
            } else {
                $safeName  = preg_replace('/[^a-z0-9\-]/', '', strtolower($user->username));
                $planSlug  = strtolower($plan->name);
                $suffix    = substr(uniqid(), -5);
                $finalName = "{$planSlug}-{$safeName}-{$suffix}";
            }

            // Memory governance: cap individual bot at 400MB regardless of plan to prevent OOM cascade.
            // Baileys + Node 18 + a few plugins fits comfortably under 350MB RSS in steady state;
            // 50MB headroom absorbs spikes. Above 400MB the bot is almost always leaking.
            $memoryCap = min((int) $plan->memory, 400);
            $swapCap   = 256; // Allow controlled swap usage instead of hard kill at RAM limit.

            // Capacity check — refuse to provision if the node is already over-committed.
            if (!$this->nodeHasCapacityFor($config->node_id, $memoryCap)) {
                Log::warning("WolfXcore provisioning: node {$config->node_id} over-committed; refusing to deploy {$memoryCap}MB bot.");
                throw new DisplayException(
                    'We can\'t deploy your bot right now — our servers are at full capacity. ' .
                    'Please try again in a few minutes, or contact support if this keeps happening. ' .
                    'No charge has been finalized for this attempt.'
                );
            }

            return $this->creator->handle([
                'name'                => $finalName,
                'owner_id'            => $user->id,
                'node_id'             => $config->node_id,
                'nest_id'             => $config->nest_id,
                'egg_id'              => $config->egg_id,
                'allocation_id'       => $allocation->id,
                'memory'              => $memoryCap,
                'swap'                => $swapCap,
                'disk'                => $plan->disk,
                'io'                  => $plan->io ?? 500,
                'cpu'                 => $plan->cpu,
                'threads'             => null,
                'oom_disabled'        => false, // let kernel kill THIS bot, not other tenants
                'image'               => $dockerImage,
                'startup'             => $startup,
                'environment'         => $environment,
                'start_on_completion' => false,
                'skip_scripts'        => false,
                'databases'           => $plan->databases ?? 0,
                'backups'             => $plan->backups ?? 0,
            ]);
        } catch (DisplayException $e) {
            // User-facing capacity / validation errors must propagate so the caller can surface them.
            throw $e;
        } catch (\Throwable $e) {
            Log::error('WolfXcore server provisioning failed: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Check if the node has enough RAM headroom for a new bot of the given memory size.
     * Allows up to 1.5× oversubscription (since not all bots use their max RAM at once).
     */
    public function nodeHasCapacityFor(int $nodeId, int $memoryMb): bool
    {
        $node = DB::table('nodes')->where('id', $nodeId)->first();
        if (!$node) return false;

        $node_memory = (int) ($node->memory ?? 0);
        $overcommit  = (int) ($node->memory_overallocate ?? 0); // percent extra allowed
        $effectiveCap = $node_memory + ((int) round($node_memory * $overcommit / 100));

        $alreadyUsed = (int) Server::where('node_id', $nodeId)->sum('memory');

        return ($alreadyUsed + $memoryMb) <= $effectiveCap;
    }

    /**
     * Provision a pre-allocated bot slot with the repo's git address and main file baked in.
     */
    public function provisionBotSlot(User $user, Plan $plan, BotRepo $repo): ?Server
    {
        try {
            $config = DB::table('wxn_server_config')->first();
            if (!$config) return null;

            $egg = Egg::with('variables')->find($config->egg_id);
            if (!$egg) return null;

            // Lock the allocation row to prevent race conditions on concurrent deploys
            $allocation = Allocation::where('node_id', $config->node_id)
                ->whereNull('server_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$allocation) {
                Log::warning('WolfXcore BotSlot: no free allocations on node ' . $config->node_id);
                return null;
            }

            $environment = [];
            foreach ($egg->variables as $variable) {
                $environment[$variable->env_variable] = $variable->default_value ?? '';
            }

            $environment['GIT_ADDRESS'] = $repo->git_address;
            $environment['MAIN_FILE']   = $repo->main_file;
            // AUTO_UPDATE=0 by default — running `git pull` on every restart caused OOMs and
            // pulled breaking commits during outages. Users can opt-in via Startup tab.
            $environment['AUTO_UPDATE'] = '0';

            $dockerImages = $egg->docker_images ?? [];
            $dockerImage  = !empty($config->docker_image_override)
                ? $config->docker_image_override
                : (is_array($dockerImages) && !empty($dockerImages)
                    ? array_values($dockerImages)[0]
                    : 'ghcr.io/wolfxcore/yolks:nodejs_18');

            $startup = !empty($config->startup_override) ? $config->startup_override : ($egg->startup ?? 'node index.js');

            $safeName  = preg_replace('/[^a-z0-9\-]/', '', strtolower($repo->name));
            $suffix    = substr(uniqid(), -5);
            $finalName = "bot-{$safeName}-{$suffix}";

            // Same 400MB cap as provision() — see that method for rationale.
            $memoryCap = min((int) $plan->memory, 400);
            $swapCap   = 256;
            if (!$this->nodeHasCapacityFor($config->node_id, $memoryCap)) {
                Log::warning("WolfXcore BotSlot: node {$config->node_id} over-committed; refusing to deploy {$memoryCap}MB bot.");
                throw new DisplayException(
                    'We can\'t deploy your bot right now — our servers are at full capacity. ' .
                    'Please try again in a few minutes, or contact support if the issue persists.'
                );
            }

            return $this->creator->handle([
                'name'                => $finalName,
                'owner_id'            => $user->id,
                'node_id'             => $config->node_id,
                'nest_id'             => $config->nest_id,
                'egg_id'              => $config->egg_id,
                'allocation_id'       => $allocation->id,
                'memory'              => $memoryCap,
                'swap'                => $swapCap,
                'disk'                => $plan->disk,
                'io'                  => $plan->io ?? 500,
                'cpu'                 => $plan->cpu,
                'threads'             => null,
                'oom_disabled'        => false,
                'image'               => $dockerImage,
                'startup'             => $startup,
                'environment'         => $environment,
                'start_on_completion' => false,
                'skip_scripts'        => false,
                'databases'           => $plan->databases ?? 0,
                'backups'             => $plan->backups ?? 0,
            ]);
        } catch (DisplayException $e) {
            // user-facing capacity refusal — let it bubble up so the caller can surface the message
            throw $e;
        } catch (\Throwable $e) {
            Log::error('WolfXcore BotSlot provisioning failed: ' . $e->getMessage());
            return null;
        }
    }
}
