<?php

namespace Tests\Feature;

use App\Models\Klinik\HapPervorma;
use App\Models\Klinik\Klinik;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Perusahaan\PerusahaanPervorma;
use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\Sekolah;
use App\Models\Sekolah\SekolahPervorma;
use App\Models\Sekolah\SekolahSession;
use App\Services\PervormaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * C3 — PervormaService 3 vertikal: stage naik di trigger benar, CAP stage-1
 * cair saat mou_at saja, idempotent (evaluasi 2x = 1 row). Rates dari
 * config/pervorma.php (C2) — test TIDAK hardcode nominal playbook di luar
 * apa yang config test sudah kunci.
 *
 * Mapping: hap → klinik, sap → sekolah, cap → perusahaan.
 */
class PervormaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function doneSessions(string $model, string $tenantKey, int $tenantId, int $count): void
    {
        foreach (range(1, $count) as $i) {
            $model::create([
                $tenantKey => $tenantId,
                'status' => 'done',
                'completed_at' => now(),
            ]);
        }
    }

    public function test_sap_stage_naik_di_trigger_5_25_40(): void
    {
        $sekolah = Sekolah::create(['name' => 'SMA Uji', 'brand' => 'SMA Uji']);
        $stages = config('pervorma.sap.stages');

        // 4 sesi → belum ada stage.
        $this->doneSessions(SekolahSession::class, 'sekolah_id', $sekolah->id, 4);
        $this->assertSame([], PervormaService::evaluate('sap', $sekolah));
        $this->assertSame(0, SekolahPervorma::count());

        // 5 sesi → stage 1 cair.
        $this->doneSessions(SekolahSession::class, 'sekolah_id', $sekolah->id, 1);
        PervormaService::evaluate('sap', $sekolah);
        $this->assertSame($stages[1]['amount'], SekolahPervorma::where('stage', 1)->sole()->amount);

        // 25 sesi → stage 2; 40 sesi → stage 3.
        $this->doneSessions(SekolahSession::class, 'sekolah_id', $sekolah->id, 20);
        PervormaService::evaluate('sap', $sekolah);
        $this->assertSame($stages[2]['amount'], SekolahPervorma::where('stage', 2)->sole()->amount);

        $this->doneSessions(SekolahSession::class, 'sekolah_id', $sekolah->id, 15);
        PervormaService::evaluate('sap', $sekolah);
        $this->assertSame($stages[3]['amount'], SekolahPervorma::where('stage', 3)->sole()->amount);
        $this->assertSame(3, SekolahPervorma::count());
    }

    public function test_cap_stage1_cair_saat_mou_at_saja_tanpa_sesi(): void
    {
        $perusahaan = Perusahaan::create(['name' => 'PT Uji', 'brand' => 'PT Uji']);
        $stages = config('pervorma.cap.stages');

        // Belum activated → tidak ada stage walau dievaluasi.
        PervormaService::evaluate('cap', $perusahaan);
        $this->assertSame(0, PerusahaanPervorma::count());

        // Activated (mou_at terisi) → stage 1 cair tanpa satu pun sesi.
        $perusahaan->update(['mou_at' => now()]);
        PervormaService::evaluate('cap', $perusahaan);
        $this->assertSame($stages[1]['amount'], PerusahaanPervorma::where('stage', 1)->sole()->amount);
        $this->assertSame(1, PerusahaanPervorma::count());

        // Stage 2/3 tetap butuh hitungan sesi (50/150).
        $this->doneSessions(PerusahaanSession::class, 'perusahaan_id', $perusahaan->id, 49);
        PervormaService::evaluate('cap', $perusahaan);
        $this->assertSame(1, PerusahaanPervorma::count());

        $this->doneSessions(PerusahaanSession::class, 'perusahaan_id', $perusahaan->id, 1);
        PervormaService::evaluate('cap', $perusahaan);
        $this->assertSame($stages[2]['amount'], PerusahaanPervorma::where('stage', 2)->sole()->amount);
    }

    public function test_idempotent_evaluator_2x_satu_row(): void
    {
        $sekolah = Sekolah::create(['name' => 'SMA Idem', 'brand' => 'SMA Idem']);
        $this->doneSessions(SekolahSession::class, 'sekolah_id', $sekolah->id, 40);

        $first = PervormaService::evaluate('sap', $sekolah);
        $second = PervormaService::evaluate('sap', $sekolah);

        $this->assertCount(3, $first);   // 3 stage cair sekaligus
        $this->assertSame([], $second);  // evaluasi ulang → tidak ada row baru
        $this->assertSame(3, SekolahPervorma::where('sekolah_id', $sekolah->id)->count());

        $perusahaan = Perusahaan::create(['name' => 'PT Idem', 'brand' => 'PT Idem', 'mou_at' => now()]);
        PervormaService::evaluate('cap', $perusahaan);
        PervormaService::evaluate('cap', $perusahaan);
        $this->assertSame(1, PerusahaanPervorma::where('perusahaan_id', $perusahaan->id)->count());
    }

    public function test_hap_delegasi_ke_driver_existing(): void
    {
        // hap tanpa sesi → no-op (driver hap butuh sesi untuk penandaan stage).
        $klinik = Klinik::create(['name' => 'Klinik Uji', 'brand' => 'Klinik Uji']);
        $this->assertSame([], PervormaService::evaluate('hap', $klinik));
        $this->assertSame(0, HapPervorma::count());

        // Vertical tidak dikenal → exception, bukan silent no-op.
        $this->expectException(\InvalidArgumentException::class);
        PervormaService::evaluate('xyz', $klinik);
    }

    public function test_vertikal_lain_tidak_tertular(): void
    {
        // Sesi sekolah tidak menghitung ke perusahaan dan sebaliknya.
        $sekolah = Sekolah::create(['name' => 'SMA A', 'brand' => 'SMA A']);
        $perusahaan = Perusahaan::create(['name' => 'PT B', 'brand' => 'PT B', 'mou_at' => now()]);
        $this->doneSessions(SekolahSession::class, 'sekolah_id', $sekolah->id, 5);

        PervormaService::evaluate('cap', $perusahaan);
        // CAP: stage 1 dari mou_at saja; stage 2 (50 sesi) TIDAK ikut cair
        // karena sesi milik sekolah.
        $this->assertSame(1, PerusahaanPervorma::where('perusahaan_id', $perusahaan->id)->count());
    }
}
