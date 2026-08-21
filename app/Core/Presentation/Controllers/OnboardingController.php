<?php

namespace App\Core\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Core\Models\UserOnboardingState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Get or initialize the onboarding tour state for the authenticated user.
     * Automatically performs version checking and resets if outdated.
     */
    public function show(Request $request, string $tourKey = 'main_product_tour'): JsonResponse
    {
        $user = $request->user();
        $currentAppVersion = (int) config("onboarding.tours.{$tourKey}.version", 1);
        $isEnabled = (bool) config("onboarding.tours.{$tourKey}.enabled", true);

        $state = UserOnboardingState::firstOrCreate(
            [
                'user_id' => $user->id,
                'tour_key' => $tourKey,
            ],
            [
                'version' => $currentAppVersion,
                'status' => 'not_started',
                'last_step_id' => null,
            ]
        );

        // Version bump detection: if application version is higher than recorded user version,
        // clear stale resume data and reset status so user receives the new tour.
        if ($state->isOutdated($currentAppVersion)) {
            $state->update([
                'version' => $currentAppVersion,
                'status' => 'not_started',
                'last_step_id' => null,
                'completed_at' => null,
                'skipped_at' => null,
            ]);
        }

        return response()->json([
            'tour_key' => $tourKey,
            'is_enabled' => $isEnabled,
            'current_version' => $currentAppVersion,
            'state' => $state,
        ]);
    }

    /**
     * Start the tour (mark in_progress).
     */
    public function start(Request $request, string $tourKey = 'main_product_tour'): JsonResponse
    {
        $user = $request->user();
        $currentAppVersion = (int) config("onboarding.tours.{$tourKey}.version", 1);

        $state = UserOnboardingState::updateOrCreate(
            [
                'user_id' => $user->id,
                'tour_key' => $tourKey,
            ],
            [
                'version' => $currentAppVersion,
                'status' => 'in_progress',
                'started_at' => now(),
            ]
        );

        return response()->json(['message' => 'Tour started', 'state' => $state]);
    }

    /**
     * Persist current step for seamless resumption.
     */
    public function step(Request $request, string $tourKey = 'main_product_tour'): JsonResponse
    {
        $validated = $request->validate([
            'step_id' => 'required|string|max:100',
        ]);

        $user = $request->user();
        $currentAppVersion = (int) config("onboarding.tours.{$tourKey}.version", 1);

        $state = UserOnboardingState::updateOrCreate(
            [
                'user_id' => $user->id,
                'tour_key' => $tourKey,
            ],
            [
                'version' => $currentAppVersion,
                'status' => 'in_progress',
                'last_step_id' => $validated['step_id'],
            ]
        );

        return response()->json(['message' => 'Step persisted', 'state' => $state]);
    }

    /**
     * Complete the tour.
     */
    public function complete(Request $request, string $tourKey = 'main_product_tour'): JsonResponse
    {
        $user = $request->user();
        $currentAppVersion = (int) config("onboarding.tours.{$tourKey}.version", 1);

        $state = UserOnboardingState::updateOrCreate(
            [
                'user_id' => $user->id,
                'tour_key' => $tourKey,
            ],
            [
                'version' => $currentAppVersion,
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );

        return response()->json(['message' => 'Tour completed', 'state' => $state]);
    }

    /**
     * Skip the tour (can be restarted later).
     */
    public function skip(Request $request, string $tourKey = 'main_product_tour'): JsonResponse
    {
        $user = $request->user();
        $currentAppVersion = (int) config("onboarding.tours.{$tourKey}.version", 1);

        $state = UserOnboardingState::updateOrCreate(
            [
                'user_id' => $user->id,
                'tour_key' => $tourKey,
            ],
            [
                'version' => $currentAppVersion,
                'status' => 'skipped',
                'skipped_at' => now(),
            ]
        );

        return response()->json(['message' => 'Tour skipped', 'state' => $state]);
    }

    /**
     * Reset tour to not_started (strictly for authenticated user).
     */
    public function reset(Request $request, string $tourKey = 'main_product_tour'): JsonResponse
    {
        $user = $request->user();
        $currentAppVersion = (int) config("onboarding.tours.{$tourKey}.version", 1);

        $state = UserOnboardingState::updateOrCreate(
            [
                'user_id' => $user->id,
                'tour_key' => $tourKey,
            ],
            [
                'version' => $currentAppVersion,
                'status' => 'not_started',
                'last_step_id' => null,
                'started_at' => null,
                'completed_at' => null,
                'skipped_at' => null,
            ]
        );

        return response()->json(['message' => 'Tour state reset', 'state' => $state]);
    }
}
