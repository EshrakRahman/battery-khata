<?php

namespace App\Services;

use App\Models\NotificationLog;

class SmsService
{
    /**
     * Send an SMS by logging it locally to sendsms.log and recording it in the database.
     */
    public function send(string $recipient, string $message): void
    {
        // 1. Write to sendsms.log
        $logPath = storage_path('logs/sendsms.log');
        $logDir = dirname($logPath);

        if (! is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = now()->toDateTimeString();
        $logLine = "[{$timestamp}] To: {$recipient} | Message: {$message}".PHP_EOL;
        file_put_contents($logPath, $logLine, FILE_APPEND);

        // 2. Create NotificationLog entry
        NotificationLog::query()->create([
            'recipient' => $recipient,
            'notification_type' => 'SMS',
            'payload' => $message,
            'delivery_status' => 'logged',
        ]);
    }
}
