<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * ONB.2 — sapu file CSV import yang tidak pernah di-commit.
 *
 * File import menyimpan password awal dalam plaintext. Jalur commit sudah
 * menghapus filenya sendiri, tapi HR yang membuka pratinjau lalu menutup tab
 * meninggalkan file di disk. Sweeper ini adalah jaring pengaman terakhir.
 */
class PruneRosterImports extends Command
{
    protected $signature = 'imports:prune {--hours=1 : Hapus file lebih tua dari N jam}';

    protected $description = 'Hapus file CSV import roster yang kedaluwarsa (berisi password plaintext)';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours((int) $this->option('hours'))->timestamp;
        $removed = 0;

        foreach ($disk->directories('private/imports') as $dir) {
            foreach ($disk->files($dir) as $file) {
                if ($disk->lastModified($file) < $cutoff) {
                    $disk->delete($file);
                    $removed++;
                }
            }
        }

        $this->info("File import dihapus: {$removed}");

        return self::SUCCESS;
    }
}