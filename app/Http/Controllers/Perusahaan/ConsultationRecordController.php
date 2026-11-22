<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Perusahaan\ConsultationNote;
use App\Models\Perusahaan\ConsultationResult;
use App\Models\Perusahaan\ConsultationReview;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Perusahaan\PerusahaanSession;
use Illuminate\Http\Request;

/**
 * W7 (K14/K16): note + hasil sesi (konsultan) dan review (karyawan).
 * Semua scoped ke sesi milik masing-masing — HR tidak punya akses
 * (route hanya di group konsultan/employee).
 */
class ConsultationRecordController extends Controller
{
    /** Konsultan: simpan/perbarui catatan sesi (encrypted cast di model). Audit tiap tulis. */
    public function storeNote(Request $r, PerusahaanSession $session)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant && $session->consultant_id === $consultant->id, 403);

        $data = $r->validate(['body' => ['required', 'string', 'max:10000']]);

        $existing = ConsultationNote::where('perusahaan_session_id', $session->id)->first();
        $action = 'note.update';

        if ($existing) {
            $existing->update(['body' => $data['body']]);
        } else {
            abort_unless(in_array($session->status, ['scheduled', 'done']), 422, 'Sesi tidak aktif.');
            ConsultationNote::create([
                'perusahaan_session_id' => $session->id,
                'consultant_id' => $consultant->id,
                'body' => $data['body'],
            ]);
            $action = 'note.create';
        }

        AuditLog::record(auth()->user(), $action, $session);

        return back()->with('success', 'Catatan sesi tersimpan.');
    }

    /** Konsultan: simpan/perbarui ringkasan + rekomendasi hasil sesi. */
    public function storeResult(Request $r, PerusahaanSession $session)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant && $session->consultant_id === $consultant->id, 403);

        $data = $r->validate([
            'summary' => ['required', 'string', 'max:10000'],
            'recommendation' => ['nullable', 'string', 'max:10000'],
        ]);

        ConsultationResult::updateOrCreate(
            ['perusahaan_session_id' => $session->id],
            ['summary' => $data['summary'], 'recommendation' => $data['recommendation'] ?? null]
        );

        return back()->with('success', 'Hasil sesi tersimpan.');
    }

    /** Karyawan: review 1x per sesi, hanya setelah status done. */
    public function storeReview(Request $r, PerusahaanSession $session)
    {
        $employee = PerusahaanEmployee::where('user_id', auth()->id())->first();
        abort_unless($employee && $session->employee_id === $employee->id, 403);

        abort_unless($session->status === 'done', 422, 'Ulasan hanya setelah sesi selesai.');
        abort_if(ConsultationReview::where('perusahaan_session_id', $session->id)->exists(), 422, 'Sesi sudah diulas.');

        $data = $r->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        ConsultationReview::create([
            'perusahaan_session_id' => $session->id,
            'perusahaan_employee_id' => $employee->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return back()->with('success', 'Terima kasih atas ulasannya.');
    }
}
