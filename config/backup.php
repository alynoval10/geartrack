<?php

return [
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('BACKUP_ADMIN_EMAILS', '')),
    ))),
    'directory' => storage_path('app/backups'),
    'max_upload_bytes' => 50 * 1024 * 1024,
    'max_unpacked_bytes' => 500 * 1024 * 1024,
    'max_entries' => 10000,
];
