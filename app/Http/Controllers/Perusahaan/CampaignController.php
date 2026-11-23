<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\EngagementCampaign;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * wv4: feature D — engagement campaign. Announcement langsung tampil di
 * Aktivitasku; email masuk antrean scheduler (SendEngagementCampaigns).
 */
class CampaignController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        return view('perusahaan.campaigns', [
            'perusahaan' => $perusahaan,
            'campaigns' => $perusahaan->campaigns()->latest('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:4000'],
            'channel' => ['required', 'in:email,announcement'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ]);

        // Announcement: langsung "terkirim" (muncul di Aktivitasku semua karyawan).
        // Email: terjadwal → dikirim command scheduler saat due.
        $isAnnouncement = $validated['channel'] === 'announcement';
        $perusahaan->campaigns()->create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'channel' => $validated['channel'],
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'sent_at' => $isAnnouncement ? now() : null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('perusahaan.campaigns.index')->with('success', 'Campaign dibuat.');
    }
}
