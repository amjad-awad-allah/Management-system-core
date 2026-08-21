<?php

namespace Tests\Feature\Core;

use Tests\TestCase;
use App\Core\Models\User;
use App\Core\Support\LocaleFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Recursively flatten a multi-dimensional array with dot notation keys.
     */
    private function flattenKeys(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenKeys($value, $newKey));
            } else {
                $result[] = $newKey;
            }
        }
        return $result;
    }

    public function test_nested_translation_key_parity_between_de_and_en(): void
    {
        $dePath = base_path('lang/de');
        $enPath = base_path('lang/en');

        $this->assertDirectoryExists($dePath);
        $this->assertDirectoryExists($enPath);

        $deFiles = array_diff(scandir($dePath), ['.', '..']);
        $enFiles = array_diff(scandir($enPath), ['.', '..']);

        sort($deFiles);
        sort($enFiles);

        $this->assertEquals($enFiles, $deFiles, 'Translation filenames must match exactly between lang/en and lang/de');

        foreach ($deFiles as $file) {
            if (!str_ends_with($file, '.php')) continue;

            $deTranslations = require "{$dePath}/{$file}";
            $enTranslations = require "{$enPath}/{$file}";

            $this->assertIsArray($deTranslations, "lang/de/{$file} must return an array");
            $this->assertIsArray($enTranslations, "lang/en/{$file} must return an array");

            $deKeys = $this->flattenKeys($deTranslations);
            $enKeys = $this->flattenKeys($enTranslations);

            $missingInDe = array_diff($enKeys, $deKeys);
            $missingInEn = array_diff($deKeys, $enKeys);

            $this->assertEmpty(
                $missingInDe,
                "Keys present in lang/en/{$file} but missing in lang/de/{$file}: " . implode(', ', $missingInDe)
            );

            $this->assertEmpty(
                $missingInEn,
                "Keys present in lang/de/{$file} but missing in lang/en/{$file}: " . implode(', ', $missingInEn)
            );
        }
    }

    public function test_default_application_locale_is_german(): void
    {
        $this->assertEquals('de', config('localization.default_locale'));
        $this->assertEquals('de', config('app.locale'));

        $res = $this->withHeader('Accept-Language', '')->getJson('/up');
        $res->assertHeader('Content-Language', 'de');
        $this->assertEquals('de', app()->getLocale());
    }

    public function test_authenticated_user_preferred_locale_takes_precedence(): void
    {
        $userEn = User::factory()->create([
            'preferred_locale' => 'en',
        ]);

        $res = $this->actingAs($userEn)->getJson('/api/v1/user');
        $res->assertStatus(200)
            ->assertHeader('Content-Language', 'en')
            ->assertJsonPath('preferred_locale', 'en');

        $this->assertEquals('en', app()->getLocale());
    }

    public function test_explicit_locale_query_override_for_documents(): void
    {
        $userEn = User::factory()->create([
            'preferred_locale' => 'en',
        ]);

        // Explicit ?locale=de overrides user DB locale for document export
        $res = $this->actingAs($userEn)->getJson('/api/v1/user?locale=de');
        $res->assertStatus(200)
            ->assertHeader('Content-Language', 'de');

        $this->assertEquals('de', app()->getLocale());
    }

    public function test_user_can_update_language_preferences(): void
    {
        $user = User::factory()->create([
            'preferred_locale' => 'de',
        ]);

        $res = $this->actingAs($user)->patchJson('/api/v1/user/preferences', [
            'preferred_locale' => 'en',
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('preferred_locale', 'en');

        $this->assertEquals('en', $user->fresh()->preferred_locale);
    }

    public function test_locale_formatter_formats_currency_and_numbers_accurately(): void
    {
        $this->assertEquals('1.234,56 €', LocaleFormatter::currency(1234.56, 'de'));
        $this->assertEquals('€1,234.56', LocaleFormatter::currency(1234.56, 'en'));

        $this->assertEquals('1.234,50', LocaleFormatter::number(1234.50, 2, 'de'));
        $this->assertEquals('1,234.50', LocaleFormatter::number(1234.50, 2, 'en'));
    }
}
