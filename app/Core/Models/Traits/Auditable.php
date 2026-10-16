<?php

namespace App\Core\Models\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logAudit('created');
        });

        static::updated(function ($model) {
            $model->logAudit('updated');
        });

        static::deleted(function ($model) {
            $model->logAudit('deleted');
        });
    }

    protected function logAudit(string $event)
    {
        $oldValues = $event !== 'created' ? $this->getOriginal() : null;
        $newValues = $event !== 'deleted' ? $this->getAttributes() : null;

        // Skip if nothing changed during update
        if ($event === 'updated' && empty($this->getChanges())) {
            return;
        }

        $hidden = method_exists($this, 'getHidden') ? $this->getHidden() : [];
        if (is_array($oldValues)) {
            foreach ($hidden as $key) {
                if (array_key_exists($key, $oldValues)) {
                    $oldValues[$key] = '[REDACTED]';
                }
            }
            $oldValues = \App\Core\Support\AuditSensitiveDataRedactor::redact($oldValues);
        }
        if (is_array($newValues)) {
            foreach ($hidden as $key) {
                if (array_key_exists($key, $newValues)) {
                    $newValues[$key] = '[REDACTED]';
                }
            }
            $newValues = \App\Core\Support\AuditSensitiveDataRedactor::redact($newValues);
        }

        DB::table('audit_logs')->insert([
            'id' => (string) Str::ulid(),
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
