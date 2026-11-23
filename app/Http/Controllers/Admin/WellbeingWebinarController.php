<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\CompanyWellbeingProgram;
use App\Models\Consultant;
use App\Models\WellbeingWebinar;
use App\Services\WellbeingQuotaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * wv2 (research2-infra §2): CRUD webinar per kontrak Wellbeing superadmin.
 * Mark-done → ledger row bucket 'webinar' event 'used' via
 * WellbeingQuotaService (append-only, guard saldo+window). No-show →
 * forfeited manual. Cancel tanpa ledger.
 */
class WellbeingWebinarController extends Controller
{
    public function index(CompanyWellbeingProgram $program)
    {
        $program->load(['wellbeingPackage', 'perusahaan']);

        return view('admin.wellbeing-webinars.index', [
            'program' => $program,
            'webinars' => $program->webinars()->orderBy('scheduled_at')->get(),
            'consultants' => Consultant::orderBy('id')->limit(200)->get(['id', 'user_id']),
        ]);
    }

    public function store(Request $request, CompanyWellbeingProgram $program)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'scheduled_at' => ['required', 'date'],
            'consultant_id' => ['nullable', 'integer', 'exists:consultants,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // QA2-LOW: pembicara harus konsultan aktif di pool tenant program —
        // konsultan platform-managed, cek pool menjaga konsistensi penugasan.
        if ($data['consultant_id'] ?? null) {
            abort_unless(
                \App\Models\ConsultantPool::where('perusahaan_id', $program->perusahaan_id)
                    ->where('consultant_id', $data['consultant_id'])
                    ->where('status', 'active')
                    ->exists(),
                422, 'Konsultan belum di-assign ke perusahaan ini.'
            );
        }

        $webinar = $program->webinars()->create([
            'title' => $data['title'],
            'scheduled_at' => $data['scheduled_at'],
            'consultant_id' => $data['consultant_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => WellbeingWebinar::STATUS_PLANNED,
        ]);

        AdminAction::record(auth()->id(), 'wellbeing_webinar.created', $webinar, [
            'program_id' => $program->id,
            'title' => $webinar->title,
        ]);

        return redirect()->route('admin.wellbeing-webinars.index', $program)
            ->with('success', "Webinar \"{$webinar->title}\" dijadwalkan.");
    }

    /** Mark done: 1 webinar = 1 kuota terpakai (ledger row, no attendance v1). */
    public function markDone(Request $request, CompanyWellbeingProgram $program, WellbeingWebinar $webinar)
    {
        // Policy memegang authz (admin + webinar milik program); mismatch → 404
        // (semantics URL asli dipertahankan, bukan 403).
        if (! Gate::allows('manage', [$webinar, $program])) {
            abort(404);
        }
        if ($webinar->status !== WellbeingWebinar::STATUS_PLANNED) {
            return back()->with('error', 'Hanya webinar berstatus planned yang bisa ditandai selesai.');
        }

        // QA4/QA7-F3: transaction + lock program row — serialize guard status,
        // lookup idempotent, insert ledger, dan update status jadi satu
        // operasi atomic (double-click konkuren tidak bisa memotong kuota 2x).
        try {
            $ledger = \Illuminate\Support\Facades\DB::transaction(function () use ($program, $webinar) {
                \App\Models\CompanyWellbeingProgram::whereKey($program->id)->lockForUpdate()->first();

                return WellbeingQuotaService::consumeWebinar($program, $webinar, auth()->user());
            });
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->with('error', $e->getMessage());
        }
        // Row idempotent sudah ada (double-submit) → jangan update audit dobel.
        if (! $ledger->wasRecentlyCreated) {
            return redirect()->route('admin.wellbeing-webinars.index', $program)
                ->with('error', 'Webinar ini sudah tercatat selesai.');
        }

        $webinar->update(['status' => WellbeingWebinar::STATUS_DONE]);

        AdminAction::record(auth()->id(), 'wellbeing_webinar.done', $webinar, [
            'program_id' => $program->id,
        ]);

        return redirect()->route('admin.wellbeing-webinars.index', $program)
            ->with('success', "Webinar \"{$webinar->title}\" selesai — 1 kuota webinar terpakai.");
    }

    /** No-show: kuota hangus (forfeited), tanpa mengubah status webinar. */
    public function forfeit(Request $request, CompanyWellbeingProgram $program)
    {
        $data = $request->validate([
            'webinar_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $webinar = $program->webinars()->whereKey($data['webinar_id'])->firstOrFail();
        // REV HIGH-1: forfeit hanya untuk webinar planned — status done/cancelled
        // (termasuk sudah pernah di-forfeit) harus ditolak, bukan hanya done.
        if ($webinar->status !== WellbeingWebinar::STATUS_PLANNED) {
            return back()->with('error', 'Hanya webinar berstatus Terjadwal yang bisa dicatat no-show.');
        }

        // QA4/QA7-F3: transaction + lock — serialisasi cek status + ledger.
        \Illuminate\Support\Facades\DB::transaction(function () use ($program, $webinar, $data) {
            \App\Models\CompanyWellbeingProgram::whereKey($program->id)->lockForUpdate()->first();
            WellbeingQuotaService::forfeitWebinar($program, $webinar, auth()->user(), $data['reason']);
        });
        $webinar->update(['status' => WellbeingWebinar::STATUS_CANCELLED]);

        AdminAction::record(auth()->id(), 'wellbeing_webinar.forfeited', $webinar, [
            'program_id' => $program->id,
            'reason' => $data['reason'],
        ]);

        return redirect()->route('admin.wellbeing-webinars.index', $program)
            ->with('success', 'No-show webinar dicatat — kuota webinar terhitung terpakai.');
    }

    /** Cancel sebelum pelaksanaan: tanpa ledger row. */
    public function cancel(Request $request, CompanyWellbeingProgram $program, WellbeingWebinar $webinar)
    {
        if (! Gate::allows('manage', [$webinar, $program])) {
            abort(404);
        }
        if ($webinar->status !== WellbeingWebinar::STATUS_PLANNED) {
            return back()->with('error', 'Hanya webinar planned yang bisa dibatalkan.');
        }

        $webinar->update(['status' => WellbeingWebinar::STATUS_CANCELLED]);

        AdminAction::record(auth()->id(), 'wellbeing_webinar.cancelled', $webinar, [
            'program_id' => $program->id,
        ]);

        return redirect()->route('admin.wellbeing-webinars.index', $program)
            ->with('success', "Webinar \"{$webinar->title}\" dibatalkan (tanpa konsumsi kuota).");
    }
}
