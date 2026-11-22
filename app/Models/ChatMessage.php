<?php

namespace App\Models;

use App\Models\Perusahaan\PerusahaanSession;
use App\Models\Sekolah\SekolahSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;

class ChatMessage extends Model
{
    use HasFactory, Prunable;

    protected $guarded = ['id'];

    protected $casts = [
        'read_at' => 'datetime',
        // K16 (research-D/F): body terenkripsi at-rest (PDP). LIKE search tidak
        // tersedia — acceptable untuk chat.
        'body' => 'encrypted',
    ];

    public function session()
    {
        return $this->belongsTo(PerusahaanSession::class, 'perusahaan_session_id');
    }

    public function sekolahSession()
    {
        return $this->belongsTo(SekolahSession::class, 'sekolah_session_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Purge 90 hari setelah sesi selesai (K11). TIDAK memotong pesan sesi
     * yang masih scheduled — retensi dihitung dari completed_at/updated_at.
     */
    public function prunable(): Builder
    {
        $cutoff = now()->subDays(config('chat.retention_days', 90));

        // Hapus pesan tua dari sesi yang sudah selesai (atau yatim) —
        // whereHas gabungan OR: kelompokkan tiap kondisi dengan cutoff-nya.
        return static::query()
            ->where(function (Builder $q) use ($cutoff) {
                $q->where(function (Builder $q2) use ($cutoff) {
                    $q2->whereHas('session', fn ($q3) => $q3->where('completed_at', '<', $cutoff))
                        ->where('created_at', '<', $cutoff);
                })->orWhere(function (Builder $q2) use ($cutoff) {
                    $q2->whereDoesntHave('session')->where('created_at', '<', $cutoff);
                });
            });
    }
}
