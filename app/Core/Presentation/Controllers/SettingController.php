<?php

namespace App\Core\Presentation\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /**
     * Get all general settings.
     */
    public function index(): JsonResponse
    {
        $settings = DB::table('settings')->pluck('value', 'key');
        return response()->json($settings);
    }

    /**
     * Update/Upsert settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $setting) {
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();
            if ($exists) {
                DB::table('settings')->where('key', $setting['key'])->update([
                    'value' => $setting['value'],
                    'updated_at' => now()
                ]);
            } else {
                DB::table('settings')->insert([
                    'id' => (string) Str::ulid(),
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }
}
