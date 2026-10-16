<?php

namespace App\Core\Support;

class AuditSensitiveDataRedactor
{
    /**
     * Exact known sensitive setting and credential keys that must be redacted.
     * Normalized to lowercase trimmed strings.
     *
     * @var list<string>
     */
    public const EXACT_SENSITIVE_KEYS = [
        'password',
        'admin_password',
        'smtp_password',
        'mail_password',
        'database_password',
        'db_password',
        'twilio_token',
        'twilio_auth_token',
        'twilio_sid',
        'api_key',
        'secret',
        'jwt_secret',
        'app_key',
        'payment_gateway_secret',
        'stripe_secret',
        'stripe_key',
        'paypal_secret',
        'webhook_secret',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'access_token',
        'refresh_token',
        'client_secret',
        'private_key',
    ];

    /**
     * Determine if a setting or attribute key is considered sensitive.
     */
    public static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(trim($key));

        // 1. Direct exact denylist match
        if (in_array($normalized, self::EXACT_SENSITIVE_KEYS, true)) {
            return true;
        }

        // 2. Explicitly exempt database identifiers and non-secret metadata suffixes
        if (preg_match('/_(?:id|type|at|by|name|count|status|url)$/', $normalized)) {
            return false;
        }

        // 3. Strict compound suffix match (e.g. custom_smtp_password, backup_api_key)
        return (bool) preg_match('/_(?:(?:smtp_|mail_|db_)?password|secret|token|credential|api_key|private_key)$/', $normalized);
    }

    /**
     * Recursively traverse and redact sensitive keys within an arbitrary array or nested data structure.
     *
     * @param mixed $data
     * @return mixed
     */
    public static function redact(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $redacted = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
                $redacted[$key] = $value !== null ? '[REDACTED]' : null;
            } elseif (is_array($value)) {
                $redacted[$key] = self::redact($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
