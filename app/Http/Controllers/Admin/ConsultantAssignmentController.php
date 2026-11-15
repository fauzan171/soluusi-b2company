<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\Consultant;
use App\Models\ConsultantPool;
use App\Models\Perusahaan\Perusahaan;
use App\Models\Sekolah\Sekolah;
use Illuminate\Http\Request;

// F2.1: superadmin assign/unassign konsultan ke pool tenant (perusahaan/sekolah).
// Pool klinik di luar scope — kolom klinik_id belum ada di consultant_pools.
class ConsultantAssignmentController extends Controller
{
    private const TENANTS = ['perusahaan', 'sekolah'];

    public function index(Request $request)
    {
        $tenant = in_array($request->query('tenant'), self::TENANTS, true)
            ? $request->query('tenant') : 'perusahaan';
        $tenantId = (int) $request->query('tenant_id');

        $tenants = $tenant === 'perusahaan'
            ? Perusahaan::orderBy('brand')->get(['id', 'brand', 'name'])
            : Sekolah::orderBy('name')->get(['id', 'name']);

        $assignedIds = $tenantId
            ? ConsultantPool::where("{$tenant}_id", $tenantId)->pluck('consultant_id')->all()
            : [];

        $consultants = Consultant::with('user:id,firstname,lastname')
            ->where('status', Consultant::ACCEPT)
            ->get()
            ->sortBy(fn ($c) => $c->user?->fullname ?? '')
            ->values();

        return view('admin.consultant-assignment', [
            'tenant' => $tenant,
            'tenantId' => $tenantId,
            'tenants' => $tenants,
            'consultants' => $consultants,
            'assignedIds' => $assignedIds,
        ]);
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'tenant' => ['required', 'in:perusahaan,sekolah'],
            'tenant_id' => ['required', 'integer', 'min:1'],
            'consultant_id' => ['required', 'integer', 'exists:consultants,id'],
        ]);

        $tenantModel = $data['tenant'] === 'perusahaan'
            ? Perusahaan::findOrFail($data['tenant_id'])
            : Sekolah::findOrFail($data['tenant_id']);

        $pool = ConsultantPool::firstOrCreate([
            'consultant_id' => $data['consultant_id'],
            "{$data['tenant']}_id" => $tenantModel->id,
        ]);

        if ($pool->wasRecentlyCreated) {
            AdminAction::record(auth()->id(), 'consultant_pool.assign', $tenantModel, [
                'tenant' => $data['tenant'],
                'consultant_id' => (int) $data['consultant_id'],
            ]);
        }

        return back()->with('success', 'Konsultan di-assign ke pool.');
    }

    public function remove(Request $request)
    {
        $data = $request->validate([
            'tenant' => ['required', 'in:perusahaan,sekolah'],
            'tenant_id' => ['required', 'integer', 'min:1'],
            'consultant_id' => ['required', 'integer', 'exists:consultants,id'],
        ]);

        $tenantModel = $data['tenant'] === 'perusahaan'
            ? Perusahaan::findOrFail($data['tenant_id'])
            : Sekolah::findOrFail($data['tenant_id']);

        $deleted = ConsultantPool::where('consultant_id', $data['consultant_id'])
            ->where("{$data['tenant']}_id", $tenantModel->id)
            ->delete();

        if ($deleted) {
            AdminAction::record(auth()->id(), 'consultant_pool.remove', $tenantModel, [
                'tenant' => $data['tenant'],
                'consultant_id' => (int) $data['consultant_id'],
            ]);
        }

        return back()->with('success', 'Konsultan dihapus dari pool.');
    }
}
