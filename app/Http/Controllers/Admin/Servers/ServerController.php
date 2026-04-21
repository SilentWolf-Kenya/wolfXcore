<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Models\Server;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Filters\AdminServerFilter;

class ServerController extends Controller
{
    /**
     * Returns all the servers that exist on the system using a paginated result set. If
     * a query is passed along in the request it is also passed to the repository function.
     */
    public function index(Request $request): View
    {
        $servers = QueryBuilder::for(Server::query()->with('node', 'user', 'allocation'))
            ->allowedFilters([
                AllowedFilter::exact('owner_id'),
                AllowedFilter::custom('*', new AdminServerFilter()),
            ])
            ->paginate(config()->get('wolfxcore.paginate.admin.servers'));

        // Build a map: server_id → subscription row (prefer direct server_id link,
        // fall back to latest active subscription for the server's owner).
        $serverIds = $servers->pluck('id')->all();
        $userIds   = $servers->pluck('user_id')->unique()->all();

        // Direct links (server_id column set) — include active and expired rows
        // so expired servers show a red badge rather than "No billing record".
        $directSubs = DB::table('wxn_subscriptions')
            ->whereIn('server_id', $serverIds)
            ->whereIn('status', ['active', 'expired'])
            ->orderByDesc('expires_at')
            ->get()
            ->groupBy('server_id')
            ->map(fn ($rows) => $rows->first());

        // User-level fallback for servers created before this feature (no server_id).
        // Only show active subscriptions here — expired legacy rows cannot be
        // reliably attributed to a specific server, so prefer "No billing record".
        $userSubs = DB::table('wxn_subscriptions')
            ->whereIn('user_id', $userIds)
            ->whereNull('server_id')
            ->where('status', 'active')
            ->orderByDesc('expires_at')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->first());

        // Merge into a map keyed by server id.
        $dueDates = [];
        foreach ($servers as $server) {
            if (isset($directSubs[$server->id])) {
                $dueDates[$server->id] = $directSubs[$server->id]->expires_at;
            } elseif (isset($userSubs[$server->user_id])) {
                $dueDates[$server->id] = $userSubs[$server->user_id]->expires_at;
            } else {
                $dueDates[$server->id] = null;
            }
        }

        return view('admin.servers.index', ['servers' => $servers, 'dueDates' => $dueDates]);
    }
}
