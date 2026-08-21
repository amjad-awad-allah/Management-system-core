<?php

namespace App\Core\Services;

use App\Core\Enums\GermanBundesland;
use App\Core\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CenterSettingsService
{
    public const CACHE_KEY = 'center_settings:v1';
    public const DEFAULT_NAME = 'Muster Nachhilfeinstitut';
    public const DEFAULT_BUNDESLAND = 'NW';

    /**
     * Get all raw settings as an associative key => value array with caching.
     *
     * @return array<string, string|null>
     */
    public function getRawSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(24), function () {
            return DB::table('settings')->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get the configured Center Name.
     */
    public function getCenterName(): string
    {
        $settings = $this->getRawSettings();
        $name = $settings['center_name'] ?? null;
        return ($name && trim($name) !== '') ? trim($name) : self::DEFAULT_NAME;
    }

    /**
     * Get the configured Center Bundesland code (e.g. 'NW', 'BY', 'BE').
     */
    public function getCenterBundesland(): string
    {
        $settings = $this->getRawSettings();
        $code = strtoupper((string) ($settings['center_bundesland'] ?? self::DEFAULT_BUNDESLAND));
        return in_array($code, GermanBundesland::values(), true) ? $code : self::DEFAULT_BUNDESLAND;
    }

    /**
     * Get the stored storage path of the center logo.
     */
    public function getCenterLogoPath(): ?string
    {
        $settings = $this->getRawSettings();
        $path = $settings['center_logo_path'] ?? null;
        return ($path && trim($path) !== '') ? trim($path) : null;
    }

    /**
     * Get base64 encoded data URI of the center logo for reliable PDF generation.
     */
    public function getCenterLogoBase64(): ?string
    {
        $path = $this->getCenterLogoPath();
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        $content = Storage::disk('public')->get($path);
        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    /**
     * Get the public asset URL of the center logo.
     */
    public function getCenterLogoUrl(): ?string
    {
        $path = $this->getCenterLogoPath();
        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Build the structured settings payload for frontend consumption.
     */
    public function getSettingsPayload(?User $user = null): array
    {
        $stateCode = $this->getCenterBundesland();
        $raw = $this->getRawSettings();

        $canManage = false;
        if ($user) {
            $canManage = $user->hasAnyRole(['Super Admin', 'Center Manager']) || $user->can('manage-settings');
        }

        return [
            'center' => [
                'name' => $this->getCenterName(),
                'bundesland' => $stateCode,
                'bundesland_name' => GermanBundesland::getName($stateCode),
                'logo_url' => $this->getCenterLogoUrl(),
            ],
            'available_bundeslaender' => GermanBundesland::toArray(),
            'raw_settings' => $raw,
            'permissions' => [
                'can_manage' => $canManage,
            ],
        ];
    }

    /**
     * Update center or general settings with transactional consistency and audit trail.
     *
     * @param array<int, array{key: string, value: string|null}>|array<string, mixed> $settings
     */
    public function updateSettings(array $settings, ?User $actor = null): void
    {
        // Normalize array format if key-value or list of objects
        $pairs = [];
        if (isset($settings[0]) && is_array($settings[0]) && isset($settings[0]['key'])) {
            foreach ($settings as $item) {
                $pairs[$item['key']] = $item['value'] ?? null;
            }
        } else {
            $pairs = $settings;
        }

        $oldSettings = $this->getRawSettings();
        $changed = [];

        DB::transaction(function () use ($pairs, $oldSettings, &$changed) {
            foreach ($pairs as $key => $value) {
                $oldVal = $oldSettings[$key] ?? null;
                $newVal = is_null($value) ? null : (string) $value;

                if ($oldVal !== $newVal) {
                    $changed[$key] = [
                        'old' => $oldVal,
                        'new' => $newVal,
                    ];

                    $exists = DB::table('settings')->where('key', $key)->exists();
                    if ($exists) {
                        DB::table('settings')->where('key', $key)->update([
                            'value' => $newVal,
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('settings')->insert([
                            'id' => (string) Str::ulid(),
                            'key' => $key,
                            'value' => $newVal,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        if (!empty($changed)) {
            $this->invalidateCache();
            $this->recordAudit('center_settings_updated', 'Setting', 'center', $changed, $actor);
        }
    }

    /**
     * Upload and store a new center logo with atomic file replacement.
     */
    public function uploadLogo(UploadedFile $file, ?User $actor = null): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            $extension = 'png';
        }

        $filename = 'logo_' . (string) Str::ulid() . '.' . $extension;
        $storedPath = $file->storeAs('logos', $filename, 'public');

        $oldPath = $this->getCenterLogoPath();

        DB::transaction(function () use ($storedPath) {
            $exists = DB::table('settings')->where('key', 'center_logo_path')->exists();
            if ($exists) {
                DB::table('settings')->where('key', 'center_logo_path')->update([
                    'value' => $storedPath,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('settings')->insert([
                    'id' => (string) Str::ulid(),
                    'key' => 'center_logo_path',
                    'value' => $storedPath,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        // Remove old logo file if it exists and differs
        if ($oldPath && $oldPath !== $storedPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->invalidateCache();
        $this->recordAudit('center_logo_uploaded', 'Setting', 'center_logo', [
            'old' => $oldPath,
            'new' => $storedPath,
        ], $actor);

        return Storage::disk('public')->url($storedPath);
    }

    /**
     * Delete center logo and clean up disk file.
     */
    public function deleteLogo(?User $actor = null): void
    {
        $oldPath = $this->getCenterLogoPath();

        DB::transaction(function () {
            DB::table('settings')->where('key', 'center_logo_path')->update([
                'value' => null,
                'updated_at' => now(),
            ]);
        });

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->invalidateCache();
        $this->recordAudit('center_logo_deleted', 'Setting', 'center_logo', [
            'old' => $oldPath,
            'new' => null,
        ], $actor);
    }

    /**
     * Invalidate cached center settings.
     */
    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Record an entry in audit_logs.
     */
    private function recordAudit(string $event, string $type, ?string $id, array $values, ?User $actor = null): void
    {
        try {
            $oldValues = array_key_exists('old', $values) 
                ? $values['old'] 
                : array_map(fn($v) => is_array($v) ? ($v['old'] ?? null) : null, $values);
            
            $newValues = array_key_exists('new', $values) 
                ? $values['new'] 
                : array_map(fn($v) => is_array($v) ? ($v['new'] ?? null) : null, $values);

            DB::table('audit_logs')->insert([
                'id' => (string) Str::ulid(),
                'user_id' => $actor?->id,
                'event' => $event,
                'auditable_type' => $type,
                'auditable_id' => $id ?: (string) Str::ulid(),
                'old_values' => json_encode($oldValues),
                'new_values' => json_encode($newValues),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Non-blocking audit failure
        }
    }
}
