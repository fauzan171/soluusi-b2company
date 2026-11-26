<?php

namespace App\Console\Commands;

use App\Models\Consultant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hapus data dummy production: tenant perusahaan demo, employees demo,
 * konsultan lokal (tanpa b2c_consultant_id), sesi/assessment/rating terkait,
 * dan user orphan. Konsultan hasil sync B2C + data referensi TIDAK disentuh.
 *
 * Urutan hapus mengikuti FK (dependensi dulu). Banyak FK lama pakai
 * restrictOnDelete (schedules, consultant_packages, certificates, dll) jadi
 * dependensi konsultan lokal harus dihapus eksplisit sebelum baris konsultannya.
 *
 * Safety: --dry-run hanya print, tidak menulis apapun. Run nyata dibungkus
 * DB transaction. Guard environment: hanya jalan di production atau bila
 * APP_ENV=testing (untuk test).
 */
class RealdataClean extends Command
{
    protected $signature = 'realdata:clean {--dry-run : Print yang akan dihapus tanpa menulis}';

    protected $description = 'Hapus dummy data production (tenant demo, konsultan lokal, sesi/rating/assessment dummy).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! app()->environment('production') && ! app()->environment('testing')) {
            $this->error('realdata:clean hanya boleh jalan di production (atau testing).');

            return self::FAILURE;
        }

        $plan = $this->buildPlan();

        $this->info('=== realdata:clean ' . ($dryRun ? 'DRY RUN (tidak menulis)' : 'EXECUTE') . ' ===');
        foreach ($plan as $step) {
            $this->line('  DELETE ' . $step['label']);
        }

        $kept = $this->keptSummary();
        foreach ($kept as $label => $count) {
            $this->line(sprintf('  KEEP %-36s : %d rows', $label, $count));
        }

        $before = $this->beforeSummary();
        $this->line('SEBELUM: ' . $before);

        if ($dryRun) {
            $this->info('Dry run selesai — tidak ada data dihapus.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Lanjut hapus data di atas?')) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan) {
            foreach ($plan as $step) {
                $step['delete']();
            }
        });

        $this->info('Selesai. ' . $this->afterSummary());

        return self::SUCCESS;
    }

    private function beforeSummary(): string
    {
        return sprintf(
            'perusahaans=%d, employees=%d, sessions=%d, consultants(local)=%d, users=%d.',
            DB::table('perusahaans')->count(),
            DB::table('perusahaan_employees')->count(),
            DB::table('perusahaan_sessions')->count(),
            Consultant::whereNull('b2c_consultant_id')->count(),
            DB::table('users')->count()
        );
    }

    /** @return array<int, array{label:string, count:int, delete:callable}> */
    private function buildPlan(): array
    {
        // 1. Tenant perusahaan dummy + dependensi cascade (sessions, employees,
        //    assessments, programs, pools, rooms, dll semua cascadeOnDelete dari perusahaans).
        $perusahaanIds = DB::table('perusahaans')->pluck('id');

        // 2. Konsultan lokal (dummy): tanpa b2c_consultant_id + bukan hasil sync.
        $localConsultantIds = Consultant::withTrashed()->whereNull('b2c_consultant_id')->pluck('id');
        $localConsultantUserIds = Consultant::whereIn('id', $localConsultantIds)
            ->pluck('user_id')->filter()->values();

        // 3. User orphan: tidak jadi konsultan (linked), tidak jadi employee,
        //    bukan superadmin, bukan user B2C sync.
        $orphanUserIds = $this->orphanUserIds();

        $steps = [];

        // Data yang hangus bersama tenant dummy (hapus eksplisit, urutan FK):
        $steps[] = $this->step('perusahaan_sessions', fn () => DB::table('perusahaan_sessions')
            ->whereIn('perusahaan_id', $perusahaanIds)->delete());
        $steps[] = $this->step('chat_messages (perusahaan)', fn () => DB::table('chat_messages')
            ->whereIn('perusahaan_session_id', DB::table('perusahaan_sessions')
                ->whereIn('perusahaan_id', $perusahaanIds)->pluck('id'))->delete());
        $steps[] = $this->step('perusahaan_assessments', fn () => DB::table('perusahaan_assessments')
            ->whereIn('perusahaan_id', $perusahaanIds)->delete());
        $steps[] = $this->step('screening_answers (perusahaan)', fn () => DB::table('screening_answers')
            ->where('assessable_type', 'App\\Models\\Perusahaan\\PerusahaanAssessment')->delete());
        $steps[] = $this->step('assessment_assignments (perusahaan)', fn () => DB::table('assessment_assignments')
            ->where('assignable_type', 'perusahaan')->whereIn('assignable_id', $perusahaanIds)->delete());
        $steps[] = $this->step('perusahaan_employees', fn () => DB::table('perusahaan_employees')
            ->whereIn('perusahaan_id', $perusahaanIds)->delete());
        $steps[] = $this->step('departements (tenant)', fn () => DB::table('departements')
            ->whereIn('perusahaan_id', $perusahaanIds)->delete());
        $steps[] = $this->step('perusahaan_admins', fn () => DB::table('perusahaan_admins')
            ->whereIn('perusahaan_id', $perusahaanIds)->delete());
        $steps[] = $this->step('perusahaans', fn () => DB::table('perusahaans')
            ->whereIn('id', $perusahaanIds)->delete());

        // Konsultan lokal: dependensi restrict dulu, lalu konsultan + user.
        // Rantai restrict via schedule konsultan lokal (sesi B2C legacy + settlement + checkout + claim).
        $localScheduleIds = fn () => DB::table('schedules')->whereIn('consultant_id', $localConsultantIds)->pluck('id');
        $localSessionIds = fn () => DB::table('consultation_sessions')->whereIn('schedule_id', $localScheduleIds())->pluck('id');
        $steps[] = $this->step('split_settlements (local sessions)', fn () => DB::table('split_settlements')
            ->whereIn('session_id', $localSessionIds())->delete());
        $steps[] = $this->step('consultation_sessions (local schedules)', fn () => DB::table('consultation_sessions')
            ->whereIn('schedule_id', $localScheduleIds())->delete());
        $steps[] = $this->step('checkout_links (local)', fn () => DB::table('checkout_links')
            ->whereIn('consultant_id', $localConsultantIds)->orWhereIn('schedule_id', $localScheduleIds())->delete());
        $steps[] = $this->step('booking_claims (local schedules)', fn () => DB::table('booking_claims')
            ->whereIn('schedule_id', $localScheduleIds())->delete());
        $steps[] = $this->step('consultant_skills (local, legacy)', fn () => DB::table('consultant_skills')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('schedules (local consultants)', fn () => DB::table('schedules')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_ratings (local)', fn () => DB::table('consultant_ratings')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_pools (local)', fn () => DB::table('consultant_pools')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_packages (local)', fn () => DB::table('consultant_packages')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('certificates (local)', fn () => DB::table('certificates')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_categories (local)', fn () => DB::table('consultant_categories')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_subcategories (local)', fn () => DB::table('consultant_subcategories')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_skills (local)', fn () => DB::table('category_skills')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_social_media (local)', fn () => DB::table('consultant_social_media')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_education (local)', fn () => DB::table('consultant_education')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultant_companies (local)', fn () => DB::table('consultant_companies')
            ->whereIn('consultant_id', $localConsultantIds)->delete());
        $steps[] = $this->step('consultants (local, force)', fn () => Consultant::withTrashed()
            ->whereIn('id', $localConsultantIds)->forceDelete());
        $steps[] = $this->step('users (local consultants)', fn () => User::whereIn('id', $localConsultantUserIds)->delete());

        // User orphan (admin/HR/employee demo lepas) — superadmin dikecualikan.
        $steps[] = $this->step('users (orphans)', fn () => User::whereIn('id', $orphanUserIds)->delete());

        return $steps;
    }

    private function step(string $label, callable $delete): array
    {
        return ['label' => $label, 'delete' => $delete];
    }

    private function orphanUserIds(): array
    {
        $linkedUserIds = DB::table('consultants')->pluck('user_id')
            ->merge(DB::table('perusahaan_employees')->pluck('user_id'))
            ->merge(DB::table('sekolah_students')->pluck('user_id'))
            ->merge(DB::table('perusahaan_admins')->pluck('user_id'))
            ->filter()->unique()->values();

        return User::whereNotIn('id', $linkedUserIds)
            ->where('email', '!=', 'admin@klinik-konsultan.local')
            ->pluck('id')
            ->values()
            ->all();
    }

    /** Count ulang tiap kondisi — dipakai dry-run table + after-verify. */
    private function keptSummary(): array
    {
        return [
            'consultants (b2c sync)' => Consultant::whereNotNull('b2c_consultant_id')->count(),
            'users b2c-*' => DB::table('users')->where('email', 'like', 'b2c-%@b2b.local')->count(),
            'roles' => DB::table('roles')->count(),
            'assessment_instruments' => DB::table('assessment_instruments')->count(),
            'screening_questions' => DB::table('screening_questions')->count(),
            'categories' => DB::table('categories')->count(),
            'users total (superadmin + b2c)' => DB::table('users')->count() - User::whereIn('id', $this->orphanUserIds())->count() - 1 + 1,
        ];
    }

    private function afterSummary(): string
    {
        return sprintf(
            'perusahaans=%d, employees=%d, sessions=%d, consultants=%d, users=%d.',
            DB::table('perusahaans')->count(),
            DB::table('perusahaan_employees')->count(),
            DB::table('perusahaan_sessions')->count(),
            DB::table('consultants')->count(),
            DB::table('users')->count()
        );
    }
}
