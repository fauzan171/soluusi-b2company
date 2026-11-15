<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hapus chat_messages untuk sesi perusahaan yang sudah done ≥24 jam.
 * Row perusahaan_sessions dan session_summaries TIDAK disentuh.
 * Log hanya agregat (count) — konten chat tidak pernah masuk log.
 */
class PurgeEndedChatTranscripts extends Command
{
    protected $signature = 'chat:purge-ended';

    protected $description = 'Purge chat transcripts 24h after session end (privacy retention).';

    public function handle(): int
    {
        $cutoff = now()->subDay();

        // Chunk by session id supaya tidak satu DELETE raksasa.
        $sessionIds = DB::table('perusahaan_sessions')
            ->where('status', 'done')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<', $cutoff)
            ->pluck('id');

        $totalMessages = 0;
        $touchedSessions = 0;
        foreach ($sessionIds->chunk(200) as $chunk) {
            $deleted = DB::table('chat_messages')
                ->whereIn('perusahaan_session_id', $chunk)
                ->delete();
            if ($deleted > 0) {
                $totalMessages += $deleted;
                $touchedSessions += $chunk->count();
            }
        }

        $this->info("Purged {$totalMessages} message(s) across {$touchedSessions} session(s).");

        return self::SUCCESS;
    }
}
