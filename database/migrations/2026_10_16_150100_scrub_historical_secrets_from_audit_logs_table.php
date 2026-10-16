<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Core\Support\AuditSensitiveDataRedactor;

return new class extends Migration
{
    /**
     * Run the migrations to scrub historical plaintext secrets.
     */
    public function up(): void
    {
        DB::table('audit_logs')
            ->orderBy('id')
            ->chunk(200, function ($records) {
                foreach ($records as $record) {
                    $needsUpdate = false;
                    $updates = [];

                    if (!empty($record->old_values) && is_string($record->old_values)) {
                        $decoded = json_decode($record->old_values, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $redacted = AuditSensitiveDataRedactor::redact($decoded);
                            $encoded = json_encode($redacted);
                            if ($encoded !== $record->old_values) {
                                $updates['old_values'] = $encoded;
                                $needsUpdate = true;
                            }
                        }
                    }

                    if (!empty($record->new_values) && is_string($record->new_values)) {
                        $decoded = json_decode($record->new_values, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $redacted = AuditSensitiveDataRedactor::redact($decoded);
                            $encoded = json_encode($redacted);
                            if ($encoded !== $record->new_values) {
                                $updates['new_values'] = $encoded;
                                $needsUpdate = true;
                            }
                        }
                    }

                    if ($needsUpdate) {
                        DB::table('audit_logs')->where('id', $record->id)->update($updates);
                    }
                }
            });
    }

    /**
     * Reverse the migrations. Redaction is irreversible by design.
     */
    public function down(): void
    {
        // Secret redaction is irreversible by design.
    }
};
