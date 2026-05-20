<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationsApiController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Auth::user()
            ->notifications()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($n) => [
                'id'         => $n->id,
                'data'       => $n->data,
                'read_at'    => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(DatabaseNotification $notification): JsonResponse
    {
        if ($notification->notifiable_id !== Auth::id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
