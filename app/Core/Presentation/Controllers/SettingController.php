<?php

namespace App\Core\Presentation\Controllers;

use App\Core\Enums\GermanBundesland;
use App\Core\Services\CenterSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function __construct(
        private readonly CenterSettingsService $centerSettings
    ) {}

    /**
     * Get center branding and system settings.
     */
    public function index(Request $request): JsonResponse
    {
        $payload = $this->centerSettings->getSettingsPayload($request->user());
        return response()->json($payload);
    }

    /**
     * Update/Upsert center or system settings.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager']) && !$user->can('manage-settings'))) {
            return response()->json(['message' => 'Unauthorized to modify center settings.'], 403);
        }

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        // Validate Bundesland code if present in the payload
        foreach ($validated['settings'] as $item) {
            if ($item['key'] === 'center_bundesland' && !empty($item['value'])) {
                if (!in_array(strtoupper($item['value']), GermanBundesland::values(), true)) {
                    return response()->json([
                        'message' => 'Invalid German Bundesland code.',
                        'errors' => ['center_bundesland' => ['The selected Bundesland is invalid.']],
                    ], 422);
                }
            }
        }

        $this->centerSettings->updateSettings($validated['settings'], $user);

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => $this->centerSettings->getSettingsPayload($user),
        ]);
    }

    /**
     * Upload a new center logo.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager']) && !$user->can('manage-settings'))) {
            return response()->json(['message' => 'Unauthorized to upload center logo.'], 403);
        }

        $request->validate([
            'logo' => 'required|file|image|mimes:png,jpg,jpeg,webp|max:3072',
        ]);

        $logoUrl = $this->centerSettings->uploadLogo($request->file('logo'), $user);

        return response()->json([
            'message' => 'Logo uploaded successfully.',
            'logo_url' => $logoUrl,
        ]);
    }

    /**
     * Stream the center logo image directly with cache headers.
     */
    public function getLogo(): \Symfony\Component\HttpFoundation\Response
    {
        $path = $this->centerSettings->getCenterLogoPath();
        if (!$path || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return response()->noContent(404);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = [
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
        ];
        $mime = $mimeMap[$ext] ?? (\Illuminate\Support\Facades\Storage::mimeType($path) ?: 'image/png');
        $fileContent = \Illuminate\Support\Facades\Storage::disk('public')->get($path);

        return response($fileContent, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
        ]);
    }

    /**
     * Remove the center logo.
     */
    public function deleteLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->hasAnyRole(['Super Admin', 'Center Manager']) && !$user->can('manage-settings'))) {
            return response()->json(['message' => 'Unauthorized to delete center logo.'], 403);
        }

        $this->centerSettings->deleteLogo($user);

        return response()->json([
            'message' => 'Logo removed successfully.',
        ]);
    }
}
