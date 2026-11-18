<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\SekolahStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ONB.4 — konfirmasi nama + pilih divisi/kelas saat login pertama.
 * Satu controller dua vertikal: `$vertical` menentukan model, sumber opsi,
 * dan route redirect. Consent TIDAK disentuh di sini (wewenang HR/BK).
 */
class ProfileController extends Controller
{
    public function show(string $vertical)
    {
        [$row, $tenant] = $this->resolve($vertical);

        return view('onboarding.profile', [
            'vertical' => $vertical,
            'row' => $row,
            'tenant' => $tenant,
            'departements' => $vertical === 'perusahaan'
                ? Departement::forPerusahaan($tenant->id)
                : collect(),
            'kelasOptions' => $vertical === 'sekolah'
                ? SekolahStudent::where('sekolah_id', $tenant->id)
                    ->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas')
                : collect(),
        ]);
    }

    public function store(Request $request, string $vertical)
    {
        [$row, $tenant] = $this->resolve($vertical);

        if ($vertical === 'perusahaan') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'departement_id' => ['nullable', 'integer'],
            ]);

            if (!empty($data['departement_id'])) {
                abort_unless(
                    Departement::forPerusahaan($tenant->id)->contains('id', (int) $data['departement_id']),
                    422,
                    'Departemen bukan milik perusahaan.'
                );
            }
        } else {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'kelas' => ['required', 'string', 'max:100'],
            ]);
            $data['kelas'] = Str::squish($data['kelas']);
        }

        $row->forceFill($data)->save();
        // Flag di user: satu sumber kebenaran untuk gerbang, independen dari row roster.
        auth()->user()->forceFill(['must_confirm_profile' => false])->save();

        return redirect()->route($vertical === 'perusahaan' ? 'perusahaan.employee' : 'sekolah.student');
    }

    /** @return array{0: PerusahaanEmployee|SekolahStudent, 1: mixed} */
    private function resolve(string $vertical): array
    {
        abort_unless(in_array($vertical, ['perusahaan', 'sekolah'], true), 404);

        $user = auth()->user();

        if ($vertical === 'perusahaan') {
            $row = PerusahaanEmployee::where('user_id', $user->id)->first();
            abort_unless($row, 403, 'Akun belum terhubung ke data karyawan.');

            // Tenant dari row sendiri: employee tidak punya membership perusahaan_admins.
            return [$row, $row->perusahaan];
        }

        $row = SekolahStudent::where('user_id', $user->id)->first();
        abort_unless($row, 403, 'Akun belum terhubung ke data siswa.');

        return [$row, $row->sekolah];
    }
}