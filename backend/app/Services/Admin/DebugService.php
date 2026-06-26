<?php

namespace App\Services\Admin;

use App\Models\Admin\AdminUsers;
use Carbon\Carbon;

class DebugService
{
    public function getLogs(?string $current_file, ?AdminUsers $admin = null): array
    {
        $path = storage_path('logs');
        $files = collect(glob($path . '/laravel-*.log'))
            ->map(fn($file) => basename($file))
            ->sortDesc()
            ->values();

        if ($files->isEmpty()) {
            return ['files' => [], 'current' => null, 'logs' => [], 'last_seen_at' => null];
        }

        $file_name = $current_file ?? $files[0];
        $lines = file($path . '/' . $file_name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_slice($lines, -200);

        $logs = [];
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (!$decoded) continue;

            if (isset($decoded['context']) && is_array($decoded['context'])) {
                // userId is auto-injected by Laravel/Monolog request context — not useful for debugging
                unset($decoded['context']['userId']);

                // Remove remaining empty values
                $decoded['context'] = array_filter(
                    $decoded['context'],
                    fn($v) => $v !== null && $v !== [] && $v !== '',
                );
            }

            $logs[] = $decoded;
        }

        return [
            'files' => $files,
            'current' => $file_name,
            'logs' => array_reverse($logs),
            'last_seen_at' => $admin?->debug_last_seen_at?->toISOString(),
        ];
    }

    public function getUnreadCount(AdminUsers $admin): int
    {
        $path = storage_path('logs');
        $log_files = glob($path . '/laravel-*.log');
        if (empty($log_files)) return 0;

        $since = $admin->debug_last_seen_at;
        $count = 0;

        foreach ($log_files as $file) {
            if ($since && filemtime($file) < $since->timestamp) continue;

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if (!$decoded || empty($decoded['datetime'])) continue;

                try {
                    $entry_time = Carbon::parse($decoded['datetime']);
                    if (!$since || $entry_time->isAfter($since)) $count++;
                } catch (\Throwable) {}
            }
        }

        return $count;
    }

    public function markSeen(AdminUsers $admin): void
    {
        $admin->update(['debug_last_seen_at' => now()]);
    }
}
