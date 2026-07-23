<?php

return [
    'host' => env('REVEAL_HOST', 'slides.localhost'),
    'url' => env('REVEAL_URL', 'http://slides.localhost'),
    'parent_origin' => env('REVEAL_PARENT_ORIGIN', env('APP_URL', 'http://localhost')),

    'archive_max_bytes' => (int) env('REVEAL_ARCHIVE_MAX_BYTES', 100 * 1024 * 1024),
    'extracted_max_bytes' => (int) env('REVEAL_EXTRACTED_MAX_BYTES', 300 * 1024 * 1024),
    'max_files' => (int) env('REVEAL_MAX_FILES', 5000),
    'token_ttl_minutes' => (int) env('REVEAL_TOKEN_TTL_MINUTES', 120),

    'allowed_extensions' => [
        'html', 'htm', 'css', 'js', 'mjs', 'json', 'map', 'xml', 'txt', 'md',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'avif',
        'woff', 'woff2', 'ttf', 'otf', 'eot',
        'mp3', 'm4a', 'ogg', 'wav', 'flac',
        'mp4', 'webm', 'ogv', 'mov',
        'pdf', 'csv', 'wasm', 'vtt', 'srt',
    ],

    'forbidden_names' => [
        '.htaccess', '.user.ini', 'web.config',
    ],

    'forbidden_extensions' => [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
        'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'bat', 'cmd', 'com', 'exe', 'dll', 'so',
    ],

    'mime_types' => [
        'html' => 'text/html; charset=UTF-8', 'htm' => 'text/html; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8', 'js' => 'application/javascript; charset=UTF-8',
        'mjs' => 'application/javascript; charset=UTF-8', 'json' => 'application/json',
        'map' => 'application/json', 'xml' => 'application/xml', 'txt' => 'text/plain; charset=UTF-8',
        'md' => 'text/markdown; charset=UTF-8', 'csv' => 'text/csv; charset=UTF-8',
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'avif' => 'image/avif',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject', 'mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4',
        'ogg' => 'audio/ogg', 'wav' => 'audio/wav', 'flac' => 'audio/flac', 'mp4' => 'video/mp4',
        'webm' => 'video/webm', 'ogv' => 'video/ogg', 'mov' => 'video/quicktime',
        'pdf' => 'application/pdf', 'wasm' => 'application/wasm', 'vtt' => 'text/vtt',
        'srt' => 'application/x-subrip',
    ],
];
