<?php

namespace App\Observers;

use App\Models\AssessmentAssignment;
use App\Models\EngagementCampaign;
use App\Models\Sekolah\Sekolah;

/**
 * B5 (D11): saat assignment perusahaan/program dibuat, bikin otomatis
 * campaign reminder via mekanisme sender yang sudah ada (atomic sent_at).
 * Sekolah tidak ikut (audience siswa, channel berbeda).
 */
class AssessmentAssignmentObserver
{
    public function created(AssessmentAssignment $assignment): void
    {
        if ($assignment->assignable_type === (new Sekolah)->getMorphClass()) {
            return;
        }
        if (! $assignment->opens_at) {
            return;
        }

        $instrument = $assignment->instrument;
        $name = $instrument?->name ?? $assignment->instrument_code;
        $perusahaanId = $this->perusahaanId($assignment);
        if (! $perusahaanId) {
            return;
        }

        $open = $assignment->opens_at->copy()->setTime(8, 0);
        EngagementCampaign::create([
            'perusahaan_id' => $perusahaanId,
            'assessment_assignment_id' => $assignment->id,
            'title' => "Asesmen {$name} sudah dibuka",
            'body' => "Asesmen {$name} tersedia untuk Anda mulai {$open->translatedFormat('d F Y')}. "
                .'Silakan isi melalui menu Assessment di dashboard Anda. Waktu pengisian sekitar 10-15 menit. '
                .'Jawaban Anda bersifat rahasia dan hanya digunakan untuk laporan agregat.',
            'channel' => 'email',
            'kind' => 'reminder',
            'scheduled_at' => $open,
            'created_by' => $assignment->assigned_by,
        ]);

        // Closing reminder hanya bila window >= 7 hari: H-3 sebelum tutup.
        $closes = $assignment->closes_at;
        $at = $closes?->copy()->subDays(3)->setTime(8, 0);
        // Skip bila H-3 sudah lewat (assignment dibuat mepet/terlambat) — jangan kirim telat.
        if ($closes && $assignment->opens_at->diffInDays($closes) >= 7 && $at->isFuture()) {
            EngagementCampaign::create([
                'perusahaan_id' => $perusahaanId,
                'assessment_assignment_id' => $assignment->id,
                'title' => "Pengingat: asesmen {$name} ditutup {$closes->translatedFormat('d F Y')}",
                'body' => "Pengingat: asesmen {$name} ditutup {$closes->translatedFormat('d F Y')}. "
                    .'Yang belum mengisi, silakan lengkapi melalui menu Assessment. '
                    .'Jawaban Anda bersifat rahasia dan hanya digunakan untuk laporan agregat.',
                'channel' => 'email',
                'kind' => 'reminder',
                'scheduled_at' => $at,
                'created_by' => $assignment->assigned_by,
            ]);
        }
    }

    /** assignment di program → perusahaan kontrak; di perusahaan → langsung. */
    private function perusahaanId(AssessmentAssignment $assignment): ?int
    {
        $assignable = $assignment->assignable;

        if ($assignable instanceof \App\Models\Perusahaan\Perusahaan) {
            return $assignable->id;
        }
        if ($assignable instanceof \App\Models\CompanyWellbeingProgram) {
            return $assignable->perusahaan_id;
        }

        return null;
    }
}
