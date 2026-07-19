<?php

namespace App\Core\Notification\Templates;

use App\Core\Models\User;

interface NotificationTemplateInterface
{
    /**
     * Generate localized title, message, and metadata.
     *
     * @param User $user Recipient user (for language check)
     * @param array $data Context data
     * @return array Contains: ['title' => string, 'message' => string, 'metadata' => array]
     */
    public function generate(User $user, array $data): array;
}
