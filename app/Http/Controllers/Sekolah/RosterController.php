<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Models\Sekolah\SekolahStudent;
use App\Support\SekolahContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Manajemen siswa (roster BK + refer konseling).
 * SATU-SATUNYA halaman guru yang menampilkan data individual — untuk
 * pengelolaan program (consent, akun). Konten sesi TIDAK pernah tampil di sini.
 * Mirror RosterController (CAP).
 */
class RosterController extends Controller
{
    public function index()
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah, 403, 'Akun tidak terhubung ke sekolah manapun.');

        $students = $sekolah->students()->orderBy('kelas')->orderBy('name')->get();

        return view('sekolah.roster', [
            'sekolah' => $sekolah,
            'students' => $students,
            'quota' => $sekolah->sessions_per_student_month,
            'slots' => \App\Services\BookingService::available('sekolah', $sekolah->id),
        ]);
    }

    public function store(Request $r)
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah, 403);

        $r->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'kelas' => ['required', 'string', 'max:20'],
            'parent_name' => ['required', 'string', 'max:255'],
        ]);

        SekolahStudent::create([
            'sekolah_id' => $sekolah->id,
            'name' => $r->name,
            'email' => $r->email,
            'kelas' => $r->kelas,
            'parent_name' => $r->parent_name,
        ]);

        return back()->with('success', "Siswa {$r->name} masuk roster. Undangan akun bisa dikirim setelah ini.");
    }

    public function consent(Request $r, SekolahStudent $student)
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah && $sekolah->is_active && $student->sekolah_id === $sekolah->id, 403);
        \App\Services\ProgramConsent::record($r, 'siswa', $student, $sekolah->id);
        return back()->with('success', 'Izin wali eksternal tercatat dengan provenance.');
    }

    /**
     * BK refer siswa → dua mode:
     * 1. Direct book: dengan schedule_id → BookingService langsung (origin bk_referral).
     * 2. Request-mode (B.2): tanpa schedule_id → sesi pending review konsultan/
     *    penjadwalan, requested_for_role='bk'. Auto-provision akun siswa dulu.
     * B.3: parent_email diisi → kirim Pemberitahuan ke orangtua (email-only,
     * tanpa akun, tanpa isi request_notes — konten sesi rahasia).
     */
    public function refer(Request $r)
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah, 403);

        $r->validate([
            'student_id' => ['required', 'exists:sekolah_students,id'],
            'konsultan_type' => ['required', 'in:psikolog,karir'],
            'schedule_id' => ['nullable', 'integer', 'exists:schedules,id'],
            'request_notes' => ['nullable', 'string', 'max:2000'],
            'topic_category' => ['nullable', 'string', 'max:100'],
            'parent_email' => ['nullable', 'email', 'max:255'],
        ]);

        $student = SekolahStudent::findOrFail($r->student_id);
        abort_unless($student->sekolah_id === $sekolah->id, 403);
        abort_unless($student->parent_consent_at, 422, 'Parent consent belum tercatat untuk siswa ini.');

        // Mode 1 — direct book (back-compat).
        if ($r->filled('schedule_id')) {
            \App\Services\Sekolah\StudentProvisioner::ensure($student);
            \App\Services\BookingService::book('sekolah', $student, [
                ...$r->only('schedule_id', 'konsultan_type'), 'origin' => 'bk_referral',
            ]);

            return back()->with('success', "Konseling untuk {$student->name} terjadwal.");
        }

        // Mode 2 — request-mode: pending review.
        abort_unless($r->filled('request_notes'), 422, 'Alasan rujukan wajib diisi untuk mode request.');

        \App\Services\Sekolah\StudentProvisioner::ensure($student);

        $session = \App\Models\Sekolah\SekolahSession::create([
            'sekolah_id' => $sekolah->id,
            'student_id' => $student->id,
            'kelas' => $student->kelas,
            'consultant_id' => null,
            'konsultan_type' => $r->konsultan_type,
            'topic_category' => $r->topic_category,
            'status' => 'scheduled', // pending = scheduled + scheduled_at NULL + consultant NULL
            'scheduled_at' => null,
            'request_notes' => $r->request_notes,
            'requested_by_user_id' => auth()->id(),
            'requested_for_role' => 'bk',
        ]);

        // B.3 — orangtua email-only. Tidak ada akun; tanpa request_notes di body.
        if ($r->filled('parent_email')) {
            Mail::to($r->parent_email)->send(
                new \App\Mail\Sekolah\ParentConsultationRequested($sekolah, $student, $session)
            );
            $session->forceFill(['parent_notified_at' => now()])->save();
        }

        return back()->with('success', "Permintaan konseling untuk {$student->name} tercatat. Menunggu review & penjadwalan.");
    }

    public function invite(Request $r)
    {
        $sekolah = SekolahContext::current();
        abort_unless($sekolah, 403);

        $r->validate(['student_id' => ['required', 'exists:sekolah_students,id']]);
        $student = SekolahStudent::findOrFail($r->student_id);
        abort_unless($student->sekolah_id === $sekolah->id, 403);
        abort_unless($student->email, 422, 'Siswa belum punya email.');

        // ponytail: Mail::raw (MAIL_MAILER=log di dev). Upgrade: templated mailable
        // saat self-register siswa live.
        $registerUrl = \App\Services\RosterInvitation::issue('siswa', $student);
        Mail::raw(
            "Halo {$student->name},\n\n"
            . "Sekolah {$sekolah->brand} mengundang kamu mengikuti program wellbeing siswa. "
            . "Tautkan akun melalui {$registerUrl}. Consent wali dicatat terpisah oleh BK sebelum asesmen dan booking.\n\n"
            . "Semua sesi bersifat rahasia — sekolah hanya melihat data agregat.\n\n"
            . "Salam,\nBK {$sekolah->brand}",
            fn ($m) => $m->to($student->email)
                ->subject("Undangan program wellbeing {$sekolah->brand}")
        );

        return back()->with('success', "Undangan terkirim ke {$student->email}.");
    }
}
