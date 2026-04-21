<?php

namespace Pterodactyl\Http\Controllers\Api\Remote;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\Log;
use Pterodactyl\Models\ActivityLog;
use Pterodactyl\Models\ActivityLogSubject;
use Pterodactyl\Models\WxnBotHealth;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Repositories\Wings\DaemonPowerRepository;
use Pterodactyl\Http\Requests\Api\Remote\ActivityEventRequest;

class ActivityProcessingController extends Controller
{
    public function __construct(private DaemonPowerRepository $power)
    {
    }

    public function __invoke(ActivityEventRequest $request)
    {
        $tz = Carbon::now()->getTimezone();

        /** @var \Pterodactyl\Models\Node $node */
        $node = $request->attributes->get('node');

        $servers = $node->servers()->whereIn('uuid', $request->servers())->get()->keyBy('uuid');
        $users = User::query()->whereIn('uuid', $request->users())->get()->keyBy('uuid');

        $logs = [];
        foreach ($request->input('data') as $datum) {
            /** @var Server|null $server */
            $server = $servers->get($datum['server']);
            if (is_null($server) || !Str::startsWith($datum['event'], 'server:')) {
                continue;
            }

            try {
                $when = Carbon::createFromFormat(
                    \DateTimeInterface::RFC3339,
                    preg_replace('/(\.\d+)Z$/', 'Z', $datum['timestamp']),
                    'UTC'
                );
            } catch (\Exception $exception) {
                Log::warning($exception, ['timestamp' => $datum['timestamp']]);

                // If we cannot parse the value for some reason don't blow up this request, just go ahead
                // and log the event with the current time, and set the metadata value to have the original
                // timestamp that was provided.
                $when = Carbon::now();
                $datum['metadata'] = array_merge($datum['metadata'] ?? [], ['original_timestamp' => $datum['timestamp']]);
            }

            $log = [
                'ip' => empty($datum['ip']) ? '127.0.0.1' : $datum['ip'],
                'event' => $datum['event'],
                'properties' => json_encode($datum['metadata'] ?? []),
                // We have to change the time to the current timezone due to the way Laravel is handling
                // the date casting internally. If we just leave it in UTC it ends up getting double-cast
                // and the time is way off.
                'timestamp' => $when->setTimezone($tz),
            ];

            if ($user = $users->get($datum['user'])) {
                $log['actor_id'] = $user->id;
                $log['actor_type'] = $user->getMorphClass();
            }

            if (!isset($logs[$datum['server']])) {
                $logs[$datum['server']] = [];
            }

            $logs[$datum['server']][] = $log;

            // wolfXcore: log crash event + bump breaker; if breaker tripped, tell Wings to stop.
            if (in_array($datum['event'], ['server:power.crashed', 'server:power.oom_killed', 'server:installer.crashed'], true)) {
                try {
                    \DB::table('wxn_bot_crashes')->insert([
                        'server_id'   => $server->id,
                        'event'       => substr($datum['event'], 0, 64),
                        'reason'      => substr(json_encode($datum['metadata'] ?? []), 0, 255),
                        'occurred_at' => now(),
                    ]);
                    $health = WxnBotHealth::recordCrash($server->id, $datum['event'] . ' ' . json_encode($datum['metadata'] ?? []));
                    if ($health->isPaused()) {
                        try {
                            $this->power->setServer($server)->send('stop');
                            Log::warning("wolfXcore breaker tripped for {$server->uuid}, stopped (until {$health->circuit_paused_until}).");
                        } catch (\Throwable $stopErr) {
                            Log::error('wolfXcore breaker stop failed: ' . $stopErr->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('wolfXcore crash tracking failed: ' . $e->getMessage(), ['exception' => $e]);
                    report($e);
                }
            }
        }

        foreach ($logs as $key => $data) {
            Assert::isInstanceOf($server = $servers->get($key), Server::class);

            $batch = [];
            foreach ($data as $datum) {
                $id = ActivityLog::insertGetId($datum);
                $batch[] = [
                    'activity_log_id' => $id,
                    'subject_id' => $server->id,
                    'subject_type' => $server->getMorphClass(),
                ];
            }

            ActivityLogSubject::insert($batch);
        }
    }
}
