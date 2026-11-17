<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hapus chat_messages untuk sesi yang sudah done ≥24 jam. Berjalan untuk
 * kedua vertical (perusahaan + sekolah). Row sesi dan session_summaries
 * TIDAK disentuh. Log hanya agregat (count) — konten chat tidak pernah masuk log.
 */
class PurgeEndedChatTranscripts extends Command
{
    protected $signature = 'chat:purge-ended';

    protected $description = 'Purge chat transcripts 24h after session end (privacy retention).';

    /** [tabel sesi, kolom FK di chat_messages] per vertical. */
    private const VERTICALS = [
        ['perusahaan_sessions', 'perusahaan_session_id'],
        ['sekolah_sessions', 'sekolah_session_id'],
    ];

    public function handle(): int
    {
        $cutoff = now()->subDay();
        $totalMessages = 0;
        $touchedSessions = 0;

        foreach (self::VERTICALS as [$table, $fk]) {
            $sessionIds = DB::table($table)
                ->where('status', 'done')
                ->whereNotNull('completed_at')
                ->where('completed_at', '<', $cutoff)
                ->pluck('id');

            foreach ($sessionIds->chunk(200) as $chunk) {
                $deleted = DB::table('chat_messages')
                    ->whereIn($fk, $chunk)
                    ->delete();
                if ($deleted > 0) {
                    $totalMessages += $deleted;
                    $touchedSessions += $chunk->count();
                }
            }
        }

        $this->info("Purged {$totalMessages} message(s) across {$touchedSessions} session(s).");

        return self::SUCCESS;
    }
}