<?php

namespace App\Core\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Notification\Models\NotificationOutbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminNotificationController extends Controller
{
    /**
     * List Outbox / DLQ items with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,processing,sent,failed,dead',
            'channel' => 'nullable|string|in:in_app,reverb,whatsapp,email,chat',
            'tenant_id' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = NotificationOutbox::with(['event'])
            ->orderBy('created_at', 'desc');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['channel'])) {
            $query->where('channel', $validated['channel']);
        }

        if (!empty($validated['tenant_id'])) {
            $query->where('tenant_id', $validated['tenant_id']);
        }

        $items = $query->paginate($validated['per_page'] ?? 25);

        return response()->json($items);
    }

    /**
     * In-Place Retry for Dead / Failed DLQ Outbox Records.
     */
    public function retry(string $id, Request $request): JsonResponse
    {
        /** @var NotificationOutbox|null $outbox */
        $outbox = NotificationOutbox::find($id);

        if (!$outbox) {
            return response()->json(['message' => 'Notification outbox record not found'], 404);
        }

        if (!in_array($outbox->status, ['dead', 'failed'], true)) {
            throw ValidationException::withMessages([
                'status' => ["Only dead or failed records can be manually retried. Current status is '{$outbox->status}'."],
            ]);
        }

        // In-place update preserving id, correlation_id, and payload
        $outbox->update([
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'error_log' => null,
        ]);

        return response()->json([
            'message' => 'Outbox record successfully scheduled for retry',
            'outbox' => $outbox->fresh(),
        ]);
    }
}
