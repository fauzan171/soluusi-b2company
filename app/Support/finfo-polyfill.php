<?php

// ponytail: cPanel VPS PHP builds (ea-php82/ea-php83 CLI) lack ext-fileinfo, which
// league/mime-type-detection instantiates unconditionally → hourly imports:prune
// crashes with "Class finfo not found". Loaded via composer autoload.files.
// Remove when the VPS PHP build ships fileinfo.
if (!defined('FILEINFO_NONE')) {
    define('FILEINFO_NONE', 0);
    define('FILEINFO_MIME_TYPE', 16);
    define('FILEINFO_MIME', 1040);
}

if (!function_exists('finfo_open')) {
    class finfo
    {
        public function __construct(public int $flags = 0, public ?string $magic_file = null) {}

        public function file(string $string, int $flags = 0, $context = null): string|false
        {
            return mime_content_type_file($string);
        }

        public function buffer(string $string, int $flags = 0, $context = null): string|false
        {
            return detect_mime_from_buffer($string);
        }
    }

    function finfo_open(int $flags = 0, ?string $magic_file = null): finfo|false
    {
        return new finfo($flags, $magic_file);
    }

    function finfo_close(finfo $finfo): bool
    {
        return true;
    }

    function finfo_file(finfo $finfo, string $string, int $flags = 0, $context = null): string|false
    {
        return $finfo->file($string, $flags, $context);
    }

    function finfo_buffer(finfo $finfo, string $string, int $flags = 0, $context = null): string|false
    {
        return $finfo->buffer($string, $flags, $context);
    }

    function mime_content_type_file(string $path): string|false
    {
        if (!is_file($path)) {
            return false;
        }
        return detect_mime_from_buffer((string) file_get_contents($path));
    }

    function detect_mime_from_buffer(string $data): string|false
    {
        if ($data === '') {
            return false;
        }
        // Match the magic patterns FinfoMimeTypeDetector consumers rely on.
        if (str_starts_with($data, "\x25PDF")) {
            return 'application/pdf';
        }
        if (str_starts_with($data, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($data, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($data, "GIF8")) {
            return 'image/gif';
        }
        if (str_starts_with($data, "PK\x03\x04")) {
            return 'application/zip';
        }
        if (strspn($data, "\x09\x0A\x0D\x20-\x7E") === strlen($data)) {
            return str_contains($data, "\x00") ? 'application/octet-stream' : 'text/plain';
        }
        return 'application/octet-stream';
    }
}
