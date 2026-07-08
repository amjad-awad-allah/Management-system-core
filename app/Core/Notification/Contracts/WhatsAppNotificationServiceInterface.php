<?php

namespace App\Core\Notification\Contracts;

interface WhatsAppNotificationServiceInterface
{
    /**
     * Send a WhatsApp message to a specific phone number.
     *
     * @param string $phoneNumber The recipient's phone number (e.g. +4917...)
     * @param string $message The text message to send.
     * @return bool True if successful, false otherwise.
     */
    public function send(string $phoneNumber, string $message): bool;
}
