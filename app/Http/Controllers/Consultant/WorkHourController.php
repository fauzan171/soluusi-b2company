<?php

namespace App\Http\Controllers\Consultant;

use App\Http\Controllers\Controller;
use App\Models\ConsultantPool;
use App\Models\ConsultantWorkHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * SLOTS: konsultan kelola jam kerja mingguan per pool perusahaan.
 * Slot booking karyawan = jam ini − booked (compute-on-read, AvailabilityService).
 * Replace-set: submit = seluruh set jam untuk pool itu diganti.
 */
class WorkHourController extends Controller
{
    /** Form: pool perusahaan aktif + jam saat ini. */
    public function hours()
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant, 403, 'Bukan konsultan.');

        $pools = ConsultantPool::where('consultant_id', $consultant->id)
            ->whereNotNull('perusahaan_id')
            ->where('status', 'active')
            ->with('perusahaan:id,name,brand', 'workHours')
            ->get();

        return view('consultant.work-hours', [
            'pools' => $pools,
            'days' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        ]);
    }

    /**
     * Simpan set jam untuk SATU pool (form per pool).
     * Validasi: weekday 0-6, H:i, end>start, tanpa overlap antar jam se-hari, max 21 baris.
     */
    public function store(Request $r)
    {
        $consultant = auth()->user()->consultants()->first();
        abort_unless($consultant, 403, 'Bukan konsultan.');

        $data = $r->validate([
            'pool_id' => ['required', 'integer'],
            'hours' => ['present', 'array', 'max:21'],
            'hours.*.weekday' => ['required', 'integer', 'between:0,6'],
            'hours.*.start_time' => ['required', 'date_format:H:i'],
            'hours.*.end_time' => ['required', 'date_format:H:i'],
        ]);

        $pool = ConsultantPool::where('id', $data['pool_id'])
            ->where('consultant_id', $consultant->id)
            ->whereNotNull('perusahaan_id')->where('status', 'active')
            ->firstOrFail();

        $hours = collect($data['hours']);
        foreach ($hours as $h) {
            if ($h['end_time'] <= $h['start_time']) {
                throw ValidationException::withMessages(['hours' => 'Jam selesai harus setelah jam mulai.']);
            }
        }
        // Overlap antar jam di hari yang sama (half-open; adjacent OK — H:i lexicographic).
        $byDay = $hours->groupBy('weekday');
        foreach ($byDay as $rows) {
            $sorted = $rows->sortBy('start_time')->values();
            for ($i = 1; $i < $sorted->count(); $i++) {
                if ($sorted[$i]['start_time'] < $sorted[$i - 1]['end_time']) {
                    throw ValidationException::withMessages(['hours' =>
                        'Jam di hari yang sama tidak boleh tumpang tindih.']);
                }
            }
        }

        DB::transaction(function () use ($pool, $hours) {
            $pool->workHours()->delete();
            foreach ($hours as $h) {
                ConsultantWorkHour::create([
                    'consultant_pool_id' => $pool->id,
                    'weekday' => $h['weekday'],
                    'start_time' => $h['start_time'],
                    'end_time' => $h['end_time'],
                ]);
            }
        });

        return redirect()->route('consultant.work-hours')
            ->with('success', 'Jam kerja tersimpan. Karyawan bisa memilih slot dari jam ini.');
    }
}
