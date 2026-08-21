<?php

namespace Tests\Feature\Core;

use App\Core\Models\User;
use App\Core\Services\CorrelationContext;
use App\Core\Services\SystemLogSanitizer;
use App\Core\Services\SystemLogService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SystemLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::whereHas('roles', function($q) {
            $q->where('name', 'Super Admin');
        })->first();

        $this->testLogFile = storage_path('logs/laravel.log');
    }

    public function test_can_fetch_system_logs_with_24h_default_filter()
    {
        Log::error('Test error message for 24h filter', [
            'route' => '/api/v1/test',
            'user' => 'admin'
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/system-logs');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                     'statistics' => ['critical_24h', 'errors_only_24h', 'total_errors_24h', 'warnings_24h', 'info_24h', 'storage_bytes', 'storage_formatted']
                 ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('ERROR', $data[0]['level']);
        $this->assertStringContainsString('Test error message for 24h filter', $data[0]['message']);
    }

    public function test_can_filter_system_logs_by_level_and_search()
    {
        Log::warning('Payment gateway response slow', ['gateway' => 'stripe']);
        Log::error('Database deadlock detected during invoice billing', ['invoice_id' => 999]);

        // Filter by level = warning
        $response = $this->actingAs($this->admin)->getJson('/api/v1/system-logs?level=warning');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertTrue(collect($data)->every(fn($item) => $item['level'] === 'WARNING'));

        // Filter by search = deadlock
        $searchResponse = $this->actingAs($this->admin)->getJson('/api/v1/system-logs?search=deadlock');
        $searchResponse->assertStatus(200);
        $searchData = $searchResponse->json('data');
        $this->assertNotEmpty($searchData);
        $this->assertStringContainsString('deadlock', strtolower($searchData[0]['message']));
    }

    public function test_multiline_exception_is_stored_as_single_structured_entry()
    {
        try {
            throw new \RuntimeException("Critical failure with multiline stack trace\nSecond line\nThird line");
        } catch (\Throwable $e) {
            Log::error('Uncaught Exception occurred', ['exception' => $e]);
        }

        $service = app(SystemLogService::class);
        $result = $service->getLogs(['period' => '24h', 'search' => 'Uncaught Exception occurred']);

        $this->assertNotEmpty($result['data']);
        $entry = $result['data'][0];
        $this->assertEquals('RuntimeException', $entry['exception_class']);
        $this->assertTrue($entry['has_trace']);
        $this->assertNotEmpty($entry['trace']);
        $this->assertNotEmpty($entry['file']);
    }

    public function test_does_not_trust_invalid_request_id_and_generates_new_one()
    {
        // Invalid request id with bad chars / huge length
        $invalidHeader = 'INVALID/../$$$' . str_repeat('X', 100);

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Request-Id', $invalidHeader)
            ->getJson('/api/v1/system-logs');

        $response->assertStatus(200);
        $responseId = $response->headers->get('X-Request-Id');
        
        $this->assertNotEquals($invalidHeader, $responseId);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{8,64}$/', $responseId);
    }

    public function test_preserves_valid_request_id()
    {
        $validId = 'REQ-VALID-TEST-12345678';

        $response = $this->actingAs($this->admin)
            ->withHeader('X-Request-Id', $validId)
            ->getJson('/api/v1/system-logs');

        $response->assertStatus(200);
        $this->assertEquals($validId, $response->headers->get('X-Request-Id'));
    }

    public function test_export_matches_active_filters_and_formats()
    {
        Log::error('Unique searchable export message 98765');

        // Test TXT export
        $txtResponse = $this->actingAs($this->admin)
            ->get('/api/v1/system-logs/export?period=24h&format=txt&search=98765');

        $txtResponse->assertStatus(200);
        $txtResponse->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('Unique searchable export message 98765', $txtResponse->streamedContent());

        // Test JSON export
        $jsonResponse = $this->actingAs($this->admin)
            ->get('/api/v1/system-logs/export?period=24h&format=json&search=98765');

        $jsonResponse->assertStatus(200);
        $jsonResponse->assertHeader('content-type', 'application/json');
        $decoded = json_decode($jsonResponse->streamedContent(), true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('logs', $decoded);
        $this->assertNotEmpty($decoded['logs']);
        $this->assertStringContainsString('98765', $decoded['logs'][0]['message']);
    }

    public function test_export_and_logs_sanitize_sensitive_data()
    {
        $sanitized = SystemLogSanitizer::sanitize([
            'email' => 'admin@bbp.de',
            'password' => 'SuperSecretPassword123!',
            'password_confirmation' => 'SuperSecretPassword123!',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
            'credit_card' => '4111222233334444',
            'authorization' => 'Bearer secret-token-xyz',
            'nested' => [
                'api_key' => 'live_pk_999999',
                'normal_field' => 'Safe value'
            ]
        ]);

        $this->assertEquals('admin@bbp.de', $sanitized['email']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['password_confirmation']);
        $this->assertEquals('[REDACTED]', $sanitized['token']);
        $this->assertEquals('[REDACTED]', $sanitized['credit_card']);
        $this->assertEquals('[REDACTED]', $sanitized['authorization']);
        $this->assertEquals('[REDACTED]', $sanitized['nested']['api_key']);
        $this->assertEquals('Safe value', $sanitized['nested']['normal_field']);
    }

    public function test_clear_system_logs_safely_and_creates_audit_log_record()
    {
        Log::info('Entry before clear');

        $response = $this->actingAs($this->admin)->postJson('/api/v1/system-logs/clear', [
            'scope' => 'all'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'cleared' => ['files_affected', 'bytes_cleared']]);

        // Verify independent audit log record was created in DB
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'event' => 'cleared',
            'auditable_type' => 'SystemLog',
        ]);
    }
}
