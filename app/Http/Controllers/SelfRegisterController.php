<?php

namespace App\Http\Controllers;

use App\Models\Perusahaan\PerusahaanEmployee;
use App\Models\Sekolah\SekolahStudent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SelfRegisterController extends Controller
{
    private function invitation(Request $request, string $type)
    {
        abort_unless(in_array($type, ['karyawan', 'siswa']), 404);
        abort_unless(is_string($request->query('token')), 403, 'Gunakan tautan undangan dari HR/BK.');
        $invitation = DB::table('roster_invitations')->where('type', $type)
            ->where('token_hash', hash('sha256', $request->query('token')))
            ->whereNull('consumed_at')->whereNull('revoked_at')->where('expires_at', '>', now())->first();
        abort_unless($invitation, 403, 'Undangan tidak berlaku. Minta HR/BK mengirim ulang.');
        return $invitation;
    }

    public function form(Request $request, string $type)
    {
        $invitation = $this->invitation($request, $type);
        return view('auth.self-register', compact('type', 'invitation'));
    }

    public function store(Request $request, string $type)
    {
        $invitation = $this->invitation($request, $type);
        // Password divalidasi HANYA di jalur yang menulis password: user baru
        // dan aktivasi provisioned (password acak di-reset). Jalur login-as-owner
        // tidak menyentuh password → tanpa field password pun sah.
        $existing = User::where('email', $invitation->email)->first();
        $activatesProvisioned = $existing && $existing->must_change_password;
        if (!$existing || $activatesProvisioned) {
            $request->validate(['password' => ['required', 'string', 'min:8']]);
        }
        $user = DB::transaction(function () use ($request, $type, $invitation) {
            // CAS consumes once on SQLite too; failed linking rolls transaction back.
            $claimed = DB::table('roster_invitations')->where('id', $invitation->id)
                ->whereNull('consumed_at')->whereNull('revoked_at')->where('expires_at', '>', now())
                ->update(['consumed_at' => now(), 'updated_at' => now()]);
            abort_unless($claimed === 1, 403, 'Undangan sudah digunakan atau dicabut.');
            $model = $type === 'karyawan' ? PerusahaanEmployee::class : SekolahStudent::class;
            $tenantKey = $type === 'karyawan' ? 'perusahaan_id' : 'sekolah_id';
            $row = $model::whereKey($invitation->roster_id)->lockForUpdate()->firstOrFail();
            abort_unless($row->$tenantKey == $invitation->tenant_id && $row->email === $invitation->email, 403);
            // Verifikasi nama pengisi = nama roster. Email dijamin invitation,
            // nama ini satu-satunya kesesuaian identitas yang tersisa. Di dalam
            // transaksi: mismatch → rollback, undangan tidak terpakai.
            abort_unless(
                strcasecmp(trim((string) $request->input('name')), trim((string) $row->name)) === 0,
                403,
                'Nama tidak sesuai data roster. Hubungi HR/BK.'
            );
            $tenant = $type === 'karyawan' ? $row->perusahaan : $row->sekolah;
            abort_unless($tenant && $tenant->is_active, 403, 'Program belum aktif. Hubungi HR/BK.');
            $role = Role::where('name', $type === 'karyawan' ? 'employee' : 'student')->firstOrFail();
            $user = User::where('email', $invitation->email)->lockForUpdate()->first();
            if ($user) {
                $otherModel = $type === 'karyawan' ? SekolahStudent::class : PerusahaanEmployee::class;
                $foreignMembership = $model::withoutGlobalScopes()->where('user_id', $user->id)->where('id', '!=', $row->id)->exists()
                    || $otherModel::withoutGlobalScopes()->where('user_id', $user->id)->exists();
                // Provisioned roster user = akun dibuat provisioner untuk email
                // ini (password acak, must_change_password masih true) dan
                // tidak punya membership roster lain: token undangan = bukti
                // kepemilikan email. Akun aktif existing bukan provisioned
                // (mis. siswa yang sudah punya password) tetap wajib login.
                $isProvisioned = $user->must_change_password
                    && $user->is_active && $user->role_id === $role->id
                    && ($row->user_id === $user->id || (!$row->user_id && !$foreignMembership));
                if ($isProvisioned) {
                    abort_if($foreignMembership, 403, 'Membership akun ambigu. Hubungi ops.');
                    // Aktivasi: ganti password acak provisioner, buka gerbang
                    // must_change_password (parity ChangePasswordController).
                    $user->forceFill([
                        'password' => Hash::make($request->password),
                        'must_change_password' => false,
                    ])->save();
                } else {
                    abort_unless(auth()->id() === $user->id && $user->is_active && $user->role_id === $role->id,
                        403, 'Login sebagai pemilik email undangan dengan role sesuai. Hubungi ops jika akun berbeda.');
                    abort_if($foreignMembership, 403, 'Membership akun ambigu. Hubungi ops.');
                }
            } else {
                abort_if($row->user_id, 403, 'Roster sudah tertaut. Hubungi ops.');
                $user = User::create([
                    'uuid' => Str::uuid(), 'username' => 'roster_'.Str::random(20),
                    'firstname' => strtok($row->name, ' '),
                    'lastname' => trim(strstr($row->name, ' ') ?: ''),
                    'email' => $invitation->email, 'password' => Hash::make($request->password),
                    'is_active' => true, 'role_id' => $role->id,
                ]);
            }
            abort_if($row->user_id && $row->user_id !== $user->id, 403, 'Roster sudah tertaut. Hubungi ops.');
            $row->update(['user_id' => $user->id]);
            return $user;
        });
        auth()->login($user);
        $request->session()->regenerate();
        return redirect()->route($type === 'karyawan' ? 'perusahaan.employee' : 'sekolah.student')
            ->with('success', 'Akun tertaut. Consent program dicatat terpisah oleh HR/BK.');
    }
}
