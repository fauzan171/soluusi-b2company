<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;

/**
 * wv4: feature B — action plan tracker per area prioritas.
 * Gate: hanya paket Growth (180d) & Impact (365d).
 */
class ActionPlanController extends Controller
{
    private const STATUSES = ['planned', 'in_progress', 'completed'];

    public function index()
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);

        $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
        $allowed = $program && in_array($program->wellbeingPackage?->code, ['growth', 'impact'], true);

        $plans = $allowed && $program
            ? $program->actionPlans()->orderBy('id')->get()
            : collect();

        return view('perusahaan.action-plan', [
            'perusahaan' => $perusahaan,
            'program' => $program,
            'allowed' => $allowed,
            'plans' => $plans,
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        $program = \App\Services\WellbeingQuotaService::activeProgram($perusahaan);
        abort_unless($program && in_array($program->wellbeingPackage?->code, ['growth', 'impact'], true), 403, 'Action Plan tersedia untuk paket Growth & Impact.');

        $validated = $request->validate([
            'area' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:planned,in_progress,completed'],
        ]);

        $program->actionPlans()->create([
            'area' => $validated['area'],
            'note' => $validated['note'] ?? null,
            'status' => $validated['status'] ?? 'planned',
        ]);

        return redirect()->route('perusahaan.action-plan.index')->with('success', 'Action plan ditambahkan.');
    }

    public function updateStatus(Request $request, ActionPlan $plan)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        $this->authorize('manage', [$plan, $perusahaan]);

        $validated = $request->validate([
            'status' => ['required', 'in:planned,in_progress,completed'],
        ]);
        $plan->update(['status' => $validated['status']]);

        return redirect()->route('perusahaan.action-plan.index')->with('success', 'Status action plan diperbarui.');
    }

    public function destroy(Request $request, ActionPlan $plan)
    {
        $perusahaan = PerusahaanContext::current();
        abort_unless($perusahaan && $perusahaan->is_active, 403);
        $this->authorize('manage', [$plan, $perusahaan]);

        $plan->delete();

        return redirect()->route('perusahaan.action-plan.index')->with('success', 'Action plan dihapus.');
    }
}
