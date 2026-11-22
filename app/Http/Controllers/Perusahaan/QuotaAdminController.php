<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan\ConsultationQuotaLedger;
use App\Services\QuotaService;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;

/**
 * W2 — refund kuota oleh HR/superadmin (MASTER-PLAN K2): event refunded (+1)
 * dengan reason wajib + audit_logs entry. Refund TIDAK pernah otomatis.
 * Tenant guard (K15): ledger harus milik karyawan perusahaan HR ini.
 */
class QuotaAdminController extends Controller
{
    public function refund(Request $r, ConsultationQuotaLedger $ledger)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan, 403, 'Akun tidak terhubung ke perusahaan manapun.');

        // Tenant scope: assignment ledger harus milik perusahaan HR ini.
        abort_unless($ledger->employeePackage?->perusahaan_id === $perusahaan->id, 403);

        $r->validate(['reason' => ['required', 'string', 'max:1000']]);

        QuotaService::refund($ledger, $r->user(), $r->string('reason')->trim()->toString());

        return back()->with('success', 'Kuota dikembalikan (event refunded tercatat di ledger).');
    }
}
