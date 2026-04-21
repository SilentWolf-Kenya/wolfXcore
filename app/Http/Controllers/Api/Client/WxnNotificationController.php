<?php

namespace Pterodactyl\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Models\WxnNotification;
use Illuminate\Support\Facades\DB;
use Pterodactyl\Http\Controllers\Controller;

class WxnNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = WxnNotification::where('is_active', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($n) use ($userId) {
                $isRead = DB::table('wxn_notification_reads')
                    ->where('user_id', $userId)
                    ->where('notification_id', $n->id)
                    ->exists();
                return [
                    'id'         => $n->id,
                    'title'      => $n->title,
                    'body'       => $n->body,
                    'type'       => $n->type,
                    'is_read'    => $isRead,
                    'created_at' => $n->created_at->toISOString(),
                ];
            });

        $unreadCount = $notifications->where('is_read', false)->count();

        return new JsonResponse([
            'notifications' => $notifications->values(),
            'unread_count'  => $unreadCount,
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        $notif  = WxnNotification::where('is_active', true)->findOrFail($id);

        DB::table('wxn_notification_reads')->updateOrInsert(
            ['user_id' => $userId, 'notification_id' => $notif->id],
            ['read_at' => now()]
        );

        return new JsonResponse(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifs = WxnNotification::where('is_active', true)->pluck('id');
        foreach ($notifs as $nid) {
            DB::table('wxn_notification_reads')->updateOrInsert(
                ['user_id' => $userId, 'notification_id' => $nid],
                ['read_at' => now()]
            );
        }

        return new JsonResponse(['success' => true]);
    }
}
