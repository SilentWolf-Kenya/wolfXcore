<?php

namespace Pterodactyl\Http\Controllers\Api\Client\Servers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\Server;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\WxnBotHealth;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Http\Controllers\Api\Client\ClientApiController;
use Pterodactyl\Http\Requests\Api\Client\Servers\SendPowerRequest;

class PowerController extends ClientApiController
{
    /**
     * PowerController constructor.
     */
    public function __construct(private DaemonPowerRepository $repository)
    {
        parent::__construct();
    }

    /**
     * Send a power action to a server.
     */
    public function index(SendPowerRequest $request, Server $server): Response
    {
        $signal = strtolower((string) $request->input('signal'));
        $isManualRecovery = in_array($signal, ['start', 'restart'], true);

        // Pre-start: run per-UUID session restore via sudo (5s ceiling). Best-effort.
        if ($signal === 'start') {
            try {
                $script = '/usr/local/bin/wolfxcore-session-restore.sh';
                if (file_exists($script)) {
                    $cmd = sprintf('timeout 5 sudo -n %s --uuid %s 2>&1', escapeshellarg($script), escapeshellarg($server->uuid));
                    $output = @shell_exec($cmd);
                    if ($output && stripos($output, 'RESTORED') !== false) {
                        Log::info("wolfXcore session restore on start {$server->uuid}: " . trim($output));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('wolfXcore pre-start restore hook failed: ' . $e->getMessage());
            }
        }

        $this->repository->setServer($server)->send($signal);

        // Manual start/restart clears the breaker (the breaker only suppresses Wings'
        // auto-restart loop — user intervention is by design always allowed).
        if ($isManualRecovery) {
            WxnBotHealth::clearForServer($server->id);
        }

        Activity::event("server:power.{$signal}")->log();

        return $this->returnNoContent();
    }
}
