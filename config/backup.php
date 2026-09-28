<?php

return [
    'admin_emails' => array_values(array_filter(array_map(
        fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('BACKUP_ADMIN_EMAILS', '')),
    ))),
    'directory' => storage_path('app/backups'),
    // Folder jaringan/cloud harus sudah di-mount oleh sistem operasi server.
    'mirror_directory' => env('BACKUP_MIRROR_DIRECTORY'),
    'automatic_time' => env('BACKUP_AUTOMATIC_TIME', '01:30'),
    'retention' => (int) env('BACKUP_RETENTION', 14),
    'automatic_creator' => env('BACKUP_AUTOMATIC_CREATOR', 'system@geartrack.local'),
    'max_upload_bytes' => 50 * 1024 * 1024,
    'max_unpacked_bytes' => 500 * 1024 * 1024,
    'max_entries' => 10000,
];
