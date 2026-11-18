<?php

namespace App\Jobs;

use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\SekolahSession;
use App\Services\SessionSummaryGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generate ringkasan AI untuk satu sesi yang baru selesai (status done).
 * Thin wrapper (C.2): resolve model per vertikal → delegate ke
 * SessionSummaryGenerator. Logic (redaksi, idempotency, gate AI, provider
 * call) hidup di service supaya perusahaan + sekolah share satu jalur.
 */
class GenerateSessionSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $sessionId,
        public readonly string $vertical = 'perusahaan',
    ) {
    }

    public function handle(): void
    {
        $session = match ($this->vertical) {
            'sekolah' => SekolahSession::find($this->sessionId),
            default => PerusahaanSession::find($this->sessionId),
        };

        if (!$session) {
            return; // sesi sudah dihapus — jangan crash worker
        }

        app(SessionSummaryGenerator::class)->generate($session);
    }
}
