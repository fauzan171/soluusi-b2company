<?php

namespace App\Console\Commands;

use App\Models\ChatMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * W5 (K11): re-encrypt row chat plaintext lama sebelum cast `encrypted` aktif.
 * Idempotent: row yang sudah encrypted (payload base64 JSON decryptable) di-skip;
 * row plaintext di-encrypt in-place via query builder (bypass cast yang kini aktif).
 * Deteksi: try decrypt() — gagal = plaintext → encrypt.
 */
class ChatEncryptExistingCommand extends Command
{
    protected $signature = 'chat:encrypt-existing';
    protected $description = 'Re-encrypt plaintext chat_messages.body rows (idempotent, skips already-encrypted)';

    public function handle(): int
    {
        $encrypted = 0;
        $skipped = 0;

        // Bypass cast: chunk pakai DB table langsung agar body terbaca raw.
        DB::table('chat_messages')->orderBy('id')->chunkById(500, function ($rows) use (&$encrypted, &$skipped) {
            foreach ($rows as $row) {
                if ($this->isEncrypted($row->body)) {
                    $skipped++;
                    continue;
                }
                DB::table('chat_messages')->where('id', $row->id)->update([
                    // encryptString (tanpa serialize) — match cast 'encrypted' di model.
                    'body' => \Illuminate\Support\Facades\Crypt::encryptString($row->body),
                ]);
                $encrypted++;
            }
        });

        $this->info("chat:encrypt-existing: encrypted={$encrypted} skipped(already)={$skipped}");

        return self::SUCCESS;
    }

    private function isEncrypted(string $body): bool
    {
        try {
            // decryptString: tanpa unserialize — payload plaintext biasa tidak
            // lolos, payload cast-encrypted lolos (cast pakai encryptString).
            \Illuminate\Support\Facades\Crypt::decryptString($body);

            return true;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return false;
        }
    }
}
