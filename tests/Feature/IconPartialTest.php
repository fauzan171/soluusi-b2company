<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Setiap `@include('...partials.icon', ['name' => 'x'])` harus resolve ke path
 * SVG yang tidak kosong. Nama icon yang salah gagal senyap (partial merender
 * <svg> kosong), jadi test ini menjaga daftar nama tetap sinkron dengan view.
 */
class IconPartialTest extends TestCase
{
    private function partialKeys(string $path): array
    {
        $src = File::get(base_path($path));
        preg_match_all("/'([a-z-]+)' => '</", $src, $m);

        return $m[1];
    }

    /** @return array{0: array<string>, 1: string} */
    private function usedIcons(string $viewsGlob): array
    {
        $used = [];
        foreach (File::glob(base_path($viewsGlob)) as $file) {
            preg_match_all("/name' => '([a-z-]+)'/", File::get($file), $m);
            foreach ($m[1] as $name) {
                $used[$name][] = basename($file);
            }
        }

        return $used;
    }

    public function test_company_portal_icons_all_resolve(): void
    {
        $keys = $this->partialKeys('resources/views/perusahaan/partials/icon.blade.php');
        $this->assertNotEmpty($keys, 'Partial icon perusahaan tidak mendefinisikan icon apa pun.');

        // SEMUA view yang meng-include partial perusahaan, bukan hanya portal employee.
        $used = $this->usedIcons('resources/views/{perusahaan,sekolah}/*.blade.php');

        $missing = array_diff(array_keys($used), $keys);
        $this->assertSame([], $missing, 'Icon tidak terdefinisi: ' . implode(', ', $missing));
    }

    public function test_school_portal_icons_all_resolve(): void
    {
        $keys = $this->partialKeys('resources/views/sekolah/partials/icon.blade.php');

        $used = [];
        foreach (File::glob(base_path('resources/views/sekolah/*.blade.php')) as $file) {
            if (! str_contains(File::get($file), "'sekolah.partials.icon'")) {
                continue;
            }
            preg_match_all("/name' => '([a-z-]+)'/", File::get($file), $m);
            foreach ($m[1] as $name) {
                $used[$name][] = basename($file);
            }
        }
        $this->assertNotEmpty($used, 'Tidak ada view sekolah yang memakai partial icon sekolah.');

        $missing = array_diff(array_keys($used), $keys);
        $this->assertSame([], $missing, 'Icon tidak terdefinisi: ' . implode(', ', $missing));
    }

    public function test_icon_paths_are_lucide_shapes_not_empty(): void
    {
        foreach (['perusahaan', 'sekolah'] as $portal) {
            $src = File::get(base_path("resources/views/{$portal}/partials/icon.blade.php"));
            preg_match_all("/'([a-z-]+)' => '(<(?:path|circle|rect|line)[^']*)'/", $src, $m, PREG_SET_ORDER);
            $this->assertGreaterThan(10, count($m), "{$portal}: set icon terlalu kecil.");

            foreach ($m as [$all, $name, $shapes]) {
                // Setiap icon minimal punya satu shape dengan geometri, dan path
                // yang ada tidak boleh terpotong (harus bergerak dari M/m).
                $this->assertMatchesRegularExpression(
                    '/(?:\sd="[Mm][^"]+"|<(?:circle|rect|line)\s)/',
                    $shapes,
                    "{$portal}/{$name}: shape SVG tidak punya geometri."
                );
                $this->assertDoesNotMatchRegularExpression(
                    '/\sd=""/',
                    $shapes,
                    "{$portal}/{$name}: path SVG kosong."
                );
            }
        }
    }
}