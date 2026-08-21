<?php

namespace App\Core\Services;

class SystemLogSanitizer
{
    protected static array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'secret',
        'token',
        'jwt',
        'access_token',
        'refresh_token',
        'authorization',
        'bearer',
        'api_key',
        'apikey',
        'cookie',
        'cookies',
        'session',
        'credit_card',
        'card_number',
        'cvv',
        'cvc',
        'pin',
        'login_code'
    ];

    /**
     * Recursively sanitize array data or scalar values.
     */
    public static function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                if (is_string($key) && self::isSensitiveKey($key)) {
                    $sanitized[$key] = '[REDACTED]';
                } else {
                    $sanitized[$key] = self::sanitize($value);
                }
            }
            return $sanitized;
        }

        if (is_string($data)) {
            return self::sanitizeString($data);
        }

        return $data;
    }

    /**
     * Check if a key name matches any sensitive keywords.
     */
    protected static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '_', ' '], '', $key));
        foreach (self::$sensitiveKeys as $sensitive) {
            $needle = strtolower(str_replace(['-', '_', ' '], '', $sensitive));
            if ($normalized === $needle || str_contains($normalized, $needle)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Redact bearer tokens or passwords from string messages if detected.
     */
    protected static function sanitizeString(string $str): string
    {
        // Redact Bearer tokens: Bearer eyJ... or Bearer [token]
        $str = preg_replace('/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i', 'Bearer [REDACTED]', $str);

        // Redact basic auth or URL credentials: http://user:pass@host
        $str = preg_replace('/(https?:\/\/[^\/:]+):([^@]+)@/i', '$1:[REDACTED]@', $str);

        return $str;
    }
}
