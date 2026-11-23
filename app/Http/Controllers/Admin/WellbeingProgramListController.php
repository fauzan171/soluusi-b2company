<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyWellbeingProgram;
use App\Models\Perusahaan\PerusahaanEmployee;
use App\Services\WellbeingQuotaService;

/**
 * wv4: admin "Program Wellbeing" index — daftar kontrak + ringkasan kuota.
 */
class WellbeingProgramListController extends Controller
{
    public function index()
    {
        $programs = CompanyWellbeingProgram::query()
            ->with(['perusahaan:id,brand,is_active', 'wellbeingPackage:id,code,name'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $programs->getCollection()->transform(function ($program) {
            $program->quota_summary = $this->quotaSummary($program);
            $program->employees_count = PerusahaanEmployee::where('perusahaan_id', $program->perusahaan_id)->count();

            return $program;
        });

        return view('admin.wellbeing-programs.index', ['programs' => $programs]);
    }

    private function quotaSummary(CompanyWellbeingProgram $program): string
    {
        $chat = max(0, (int) $program->expert_chat_quota - WellbeingQuotaService::bucketBalance($program, 'expert_chat'));
        $deep = max(0, (int) $program->deep_support_quota - WellbeingQuotaService::bucketBalance($program, 'deep_support'));

        return "chat {$chat}/{$program->expert_chat_quota} · DS {$deep}/{$program->deep_support_quota} · webinar {$program->webinar_quota}";
    }
}
