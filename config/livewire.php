<?php

$revealArchiveMaxKilobytes = (int) ceil(
    ((int) env('REVEAL_ARCHIVE_MAX_BYTES', 100 * 1024 * 1024)) / 1024
);

return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),
        'rules' => ['required', 'file', "max:{$revealArchiveMaxKilobytes}"],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => (int) env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_MAX_TIME', 20),
        'cleanup' => true,
    ],
];
