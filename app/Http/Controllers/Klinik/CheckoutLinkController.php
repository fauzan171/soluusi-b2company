<?php

namespace App\Http\Controllers\Klinik;

use App\Http\Controllers\Controller;
use App\Models\Consultant;
use App\Models\ConsultantPackage;
use App\Models\Klinik\CheckoutLink;
use App\Models\Klinik\MarkupRule;
use App\Models\Schedule;
use App\Services\Klinik\MarkupService;
use Illuminate\Http\Request;

/**
 * Admin-generate-link booking (Build-NEW #5, M3). Admin klinik WAJIB pilih
 * konsultan dulu (mirror B2C: profil konsultan → paket/jadwal). Harga
 * fixed: markup diambil dari MarkupRule yang diset Soluusi — klinik read-only,
 * tidak bisa override per-link.
 */
class CheckoutLinkController extends Controller
{
    public function index()
    {
        $klinik = \App\Support\KlinikContext::current();
        abort_unless($klinik, 403, 'Akun tidak terhubung ke klinik manapun.');
        $klinikId = $klinik->id;
        $links = CheckoutLink::where('klinik_id', $klinikId)
            ->with(['consultant.user', 'schedule', 'package'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $createdLink = null;
        if (session('created_link_id')) {
            $createdLink = CheckoutLink::where('klinik_id', $klinikId)
                ->with(['consultant.user', 'schedule', 'package'])
                ->find(session('created_link_id'));
        }

        return view('klinik.links.index', [
            'links' => $links,
            'klinik' => $klinik,
            'createdLink' => $createdLink,
        ]);
    }

    public function create(Request $request)
    {
        // Wajib pilih konsultan dulu (mirror B2C). Tanpa consultant_id → lempar ke picker.
        if (!$request->filled('consultant_id')) {
            return redirect()->route('klinik.consultants.index')
                ->with('info', 'Pilih konsultan dulu untuk melihat paket & jadwal yang tersedia.');
        }

        $selectedConsultant = Consultant::with('user')
            ->where('status', Consultant::ACCEPT)
            ->where('is_active', true)
            ->findOrFail($request->consultant_id);

        $schedules = Schedule::with('consultant.user')
            ->where('consultant_id', $selectedConsultant->id)
            ->where('klinik_id', $this->klinikId())
            ->where('status', Schedule::STATUS_CONFIRMED)
            ->where('started_at', '>', now())
            ->whereDoesntHave('consultationSession', function ($q) {
                $q->blocking();
            })
            ->orderBy('started_at')
            ->limit(100)
            ->get();

        $packages = ConsultantPackage::with('consultant.user')
            ->where('consultant_id', $selectedConsultant->id)
            ->get();

        // Markup rule yang diset Soluusi (klinik read-only).
        $markups = MarkupRule::where('klinik_id', $this->klinikId())->get()->keyBy('consultant_package_id');

        return view('klinik.links.create', compact('schedules', 'packages', 'markups', 'selectedConsultant'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'consultant_package_id' => 'required|exists:consultant_packages,id',
        ]);

        $schedule = Schedule::with('consultant')->findOrFail($request->schedule_id);
        $package = ConsultantPackage::findOrFail($request->consultant_package_id);

        abort_unless($schedule->klinik_id === $this->klinikId(), 403, 'Jadwal ini bukan milik klinik kamu.');
        abort_unless(
            $schedule->status === Schedule::STATUS_CONFIRMED && $schedule->started_at->isFuture(),
            422,
            'Jadwal sudah tidak aktif.'
        );
        abort_unless(
            $schedule->consultant
                && $schedule->consultant->status === Consultant::ACCEPT
                && (bool) $schedule->consultant->is_active,
            422,
            'Konsultan sudah tidak aktif.'
        );
        abort_unless($schedule->consultant_id === $package->consultant_id, 422, 'Paket dan jadwal harus dari konsultan yang sama.');
        if (($schedule->mode ?? Schedule::MODE_ONLINE) !== ($package->mode ?? ConsultantPackage::MODE_ONLINE)) {
            return back()
                ->with('error', 'Mode paket dan mode jadwal harus sama. Pilih paket yang sesuai dengan jadwal.')
                ->withInput();
        }

        // Slot lock (§17.7 mirror): tolak bila sudah ada sesi aktif di slot ini.
        $booked = \App\Models\ConsultationSession::where('schedule_id', $schedule->id)
            ->blocking()->exists();
        if ($booked) {
            return back()->with('error', 'Jadwal sudah dipesan. Pilih slot lain.')->withInput();
        }

        $hasActiveLink = CheckoutLink::where('schedule_id', $schedule->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
        if ($hasActiveLink) {
            return redirect()->route('klinik.links.index')
                ->with('created_link_id', $hasActiveLink->id)
                ->with('info', 'Jadwal sudah punya checkout link aktif. Gunakan link yang sudah ada.');
        }

        // Harga fixed Soluusi: markup SELALU dari MarkupRule, tidak dari input.
        $net = (int) $package->price;
        $markup = (int) (optional(
            MarkupRule::where('klinik_id', $this->klinikId())->where('consultant_package_id', $package->id)->first()
        )->markup_amount ?? 0);
        $final = $net + $markup;

        abort_unless(
            MarkupService::isValid($net, $final),
            422,
            'Markup paket melebihi batas harga yang diizinkan. Hubungi super admin.'
        );

        $link = CheckoutLink::create([
            'token' => CheckoutLink::generateToken(),
            'klinik_id' => $this->klinikId(),
            'consultant_id' => $schedule->consultant_id,
            'schedule_id' => $schedule->id,
            'consultant_package_id' => $package->id,
            'net_price' => $net,
            'markup_amount' => $markup,
            'final_price' => $final,
            'created_by' => auth()->id(),
            'expires_at' => now()->addDay(),
        ]);

        $msg = 'Link customer berhasil dibuat: ' . route('checkout.show', $link->token)
            . ' — harga Rp' . number_format($final, 0, ',', '.')
            . ' (net Rp' . number_format($net, 0, ',', '.') . ' + margin Rp' . number_format($markup, 0, ',', '.') . ')';

        return redirect()->route('klinik.links.index')
            ->with('created_link_id', $link->id)
            ->with('success', $msg);
    }

    protected function klinikId(): int
    {
        $klinik = \App\Support\KlinikContext::current();
        abort_unless($klinik, 403, 'Akun tidak terhubung ke klinik manapun.');
        return $klinik->id;
    }
}
