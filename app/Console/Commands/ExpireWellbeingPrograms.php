<?php

namespace App\Console\Commands;

use App\Models\AdminAction;
use App\Models\CompanyWellbeingProgram;
use Illuminate\Console\Command;

/**
 * wb-T4 (bizrules R6) — daily sweeper terminasi 30-day notice + kontrak lewat
 * masa: program active dengan (terminated_at <= today ATAU ends_at < today) →
 * status ended + AdminAction. Kuota hangus otomatis (saldo dihitung hanya
 * untuk program aktif — R1). Dijadwalkan di Kernel::schedule harian; prod
 * butuh `schedule:run` aktif (pola sama sessions:sweep-no-show).
 */
class ExpireWellbeingPrograms extends Command
{
    protected $signature = 'wellbeing:expire-programs';
    protected $description = 'Akhiri program Wellbeing aktif yang tanggal efektif terminasi / ends_at-nya telah lewat';

    public function handle(): int
    {
        $stale = CompanyWellbeingProgram::query()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereDate('terminated_at', '<=', today())
                    ->orWhereDate('ends_at', '<', today());
            })
            ->get();

        foreach ($stale as $program) {
            $program->update(['status' => 'ended']);
            AdminAction::record(
                (int) ($program->created_by ?? 0),
                'wellbeing_program.ended',
                $program,
                ['reason' => $program->terminated_at ? 'terminasi 30-day notice efektif' : 'kontrak berakhir (ends_at)']
            );
        }

        $this->info("Programs ended: {$stale->count()}.");

        return self::SUCCESS;
    }
}
