<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['is_read' => true]);

        return $this->counts($request);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->appNotifications()->unread()->update(['is_read' => true]);

        return $this->counts($request);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $request->user()->appNotifications()->delete();

        return $this->counts($request);
    }

    public function destroy(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        return $this->counts($request);
    }

    public function counts(Request $request): JsonResponse
    {
        $total = $request->user()->appNotifications()->count();
        $unread = $request->user()->appNotifications()->where('is_read', false)->count();

        return response()->json(['total' => $total, 'unread' => $unread]);
    }
}
