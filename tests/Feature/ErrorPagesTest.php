<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * T7.6 — Error pages 403/404/500 render dengan app name (white-label safe).
 */
class ErrorPagesTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('errorPages')]
    public function test_error_page_renders_with_app_name(string $code, string $heading)
    {
        config(['app.name' => 'Acme Wellbeing']);
        $html = Blade::render(file_get_contents(resource_path("views/errors/{$code}.blade.php")));

        $this->assertStringContainsString($heading, $html);
        $this->assertStringContainsString('Acme Wellbeing', $html);
        $this->assertStringNotContainsString('Soluusi', $html, 'brand hardcode bocor — white-label unsafe');
    }

    public static function errorPages(): array
    {
        return [
            '403' => ['403', 'Akses ditolak'],
            '404' => ['404', 'Halaman tidak ditemukan'],
            '500' => ['500', 'Terjadi kesalahan'],
        ];
    }
}
