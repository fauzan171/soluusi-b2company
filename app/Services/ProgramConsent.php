<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramConsent
{
    public static function record(Request $request, string $type, $row, int $tenantId): void
    {
        $data = $request->validate([
            'granted' => ['required', 'boolean'],
            'evidence' => ['required', 'string', 'max:2000'],
            'agreed_at' => ['required', 'date', 'before_or_equal:now'],
        ]);
        DB::transaction(function () use ($data, $type, $row, $tenantId) {
            $row = $row->newQuery()->whereKey($row->id)->lockForUpdate()->firstOrFail();
            $tenantKey = $type === 'karyawan' ? 'perusahaan_id' : 'sekolah_id';
            abort_unless($row->$tenantKey === $tenantId, 403);
            DB::table('program_consent_history')->insert([
                'type' => $type, 'tenant_id' => $tenantId, 'roster_id' => $row->id,
                'actor_id' => auth()->id(), 'granted' => $data['granted'],
                'evidence' => $data['evidence'], 'agreed_at' => $data['agreed_at'], 'recorded_at' => now(),
            ]);
            $row->update([$type === 'karyawan' ? 'consent_at' : 'parent_consent_at' => $data['granted'] ? $data['agreed_at'] : null]);
        });
    }
}
