<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\Room;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;

/**
 * W5 (M5): Room 1-on-1 — CRUD admin perusahaan (name + status active/inactive).
 * Tenant-scoped via PerusahaanContext (K15: route guard role:hr_admin,admin).
 */
class RoomController extends Controller
{
    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403);

        $rooms = Room::where('perusahaan_id', $perusahaan->id)->orderBy('id')->get();

        return view('perusahaan.rooms', ['perusahaan' => $perusahaan, 'rooms' => $rooms]);
    }

    public function store(Request $r)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403);

        $r->validate(['name' => ['required', 'string', 'max:100']]);

        Room::create([
            'perusahaan_id' => $perusahaan->id,
            'name' => trim($r->name),
            'status' => 'active',
        ]);

        return redirect()->route('perusahaan.rooms.index')
            ->with('success', 'Ruang ditambahkan.');
    }

    public function toggle(Room $room)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $room->perusahaan_id === $perusahaan->id, 403);

        $room->update(['status' => $room->status === 'active' ? 'inactive' : 'active']);

        return redirect()->route('perusahaan.rooms.index')
            ->with('success', 'Status ruang diubah.');
    }
}
