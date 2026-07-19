<?php

namespace App\Core\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationCenterController extends Controller
{
    /**
     * List current authenticated user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Only fetch in_app channel notifications for the notification center feed
        $notifications = Notification::where('user_id', $userId)
            ->where('delivery_channel', 'in_app')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $userId = $request->user()->id;

        $notification = Notification::where('user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read', 'notification' => $notification]);
    }

    /**
     * Mark all user's notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        Notification::where('user_id', $userId)
            ->where('delivery_channel', 'in_app')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Get the authenticated user's notification preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $preferences = \App\Core\Models\NotificationPreference::where('user_id', $userId)->get();
        return response()->json($preferences);
    }

    /**
     * Update the authenticated user's notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.channel' => 'required|string|in:in_app,chat,whatsapp',
            'preferences.*.enabled' => 'required|boolean',
            'preferences.*.quiet_hours_start' => 'nullable|string|max:5',
            'preferences.*.quiet_hours_end' => 'nullable|string|max:5',
        ]);

        $userId = $request->user()->id;

        foreach ($validated['preferences'] as $item) {
            \App\Core\Models\NotificationPreference::updateOrCreate(
                [
                    'user_id' => $userId,
                    'notification_type' => $item['notification_type'],
                    'channel' => $item['channel']
                ],
                [
                    'enabled' => $item['enabled'],
                    'quiet_hours_start' => $item['quiet_hours_start'] ?? null,
                    'quiet_hours_end' => $item['quiet_hours_end'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Notification preferences updated successfully']);
    }
}
