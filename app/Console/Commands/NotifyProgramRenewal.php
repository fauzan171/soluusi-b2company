<?php

namespace App\Console\Commands;

use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\PerusahaanAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * wv4 — daily renewal reminder: program aktif ends_at < 30 hari → email
 * HR admin perusahaan (pola Mail::raw, tanpa tabel notifications).
 */
class NotifyProgramRenewal extends Command
{
    protected $signature = 'wellbeing:renewal-notice';
    protected $description = 'Ingatkan HR admin untuk kontrak Wellbeing yang berakhir < 30 hari';

    public function handle(): int
    {
        $expiring = CompanyWellbeingProgram::query()
            ->where('status', 'active')
            ->whereNull('terminated_at')
            ->where('ends_at', '>=', today())
            ->where('ends_at', '<', today()->addDays(30))
            ->with(['perusahaan', 'wellbeingPackage'])
            ->get();

        $notified = 0;
        foreach ($expiring as $program) {
            $perusahaan = $program->perusahaan;
            if (! $perusahaan) {
                continue;
            }
            $admins = PerusahaanAdmin::where('perusahaan_id', $perusahaan->id)
                ->with('user:id,email,firstname')
                ->get()
                ->filter(fn ($a) => $a->user?->email);
            $days = (int) today()->diffInDays($program->ends_at, false);
            foreach ($admins as $admin) {
                Mail::raw(
                    "Halo {$admin->user->firstname},\n\nKontrak Wellbeing {$perusahaan->brand} (paket {$program->wellbeingPackage?->name}) berakhir dalam {$days} hari ({$program->ends_at->format('d M Y')}).\nHubungi tim Soluusi untuk perpanjangan.\n\nSalam,\nSoluusi",
                    fn ($m) => $m->to($admin->user->email)->subject('Pengingat perpanjangan kontrak Wellbeing')
                );
            }
            $notified++;
        }

        $this->info("Renewal notices sent for {$notified} program(s).");

        return self::SUCCESS;
    }
}
