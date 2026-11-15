<?php

namespace App\Services;

use App\Support\AggregatePrivacy;
use Illuminate\Support\Carbon;

/**
 * Metrik tambahan untuk dashboard visual HR (perusahaan) & BK (sekolah).
 * Semua angka AGREGAT — tidak pernah menyentuh data individual di layar.
 * Dipakai bersama ProgramAggregate::monthly() sebagai pelengkap.
 */
class DashboardMetrics
{
    public static function build($tenant, string $vertical): array
    {
        $school = $vertical === 'sekolah';
        $person = $school ? 'student_id' : 'employee_id';
        $roster = $school ? $tenant->students() : $tenant->employees();
        $quotaPer = $school ? $tenant->sessions_per_student_month : $tenant->sessions_per_employee_month;

        [$month, $start, $end] = AggregatePrivacy::month();
        $prevStart = $start->copy()->subMonth();
        $prevEnd = $start;

        $sessionsAll = $tenant->sessions()->where('scheduled_at', '>=', $start)
            ->where('scheduled_at', '<', $end)->get();
        $doneMonth = $sessionsAll->where('status', 'done')->count();
        $prevDone = $tenant->sessions()->where('scheduled_at', '>=', $prevStart)
            ->where('scheduled_at', '<', $prevEnd)->where('status', 'done')->count();

        $scheduled = $tenant->sessions()->whereIn('status', ['scheduled'])->where('scheduled_at', '>=', now())->get();
        $scheduledNext7Days = $scheduled->where('scheduled_at', '<=', now()->copy()->addDays(7))->count();

        $members = $roster->get();
        $registered = $members->count();

        $activatedPeople = $tenant->sessions()->where('status', 'done')->pluck($person)->filter()->unique();
        $activeMonthPeople = $sessionsAll->whereIn('status', ['done', 'scheduled'])->pluck($person)->filter()->unique();

        $contractCount = (int) ($school ? ($tenant->student_count ?: 0) : ($tenant->employee_count ?: 0));
        $contractGap = max(0, $contractCount - $registered);

        $ratings = $tenant->sessions()->where('status', 'done')->whereNotNull('rating')->get();
        $avgRating = $ratings->isNotEmpty() ? round($ratings->avg('rating'), 1) : 0.0;
        $ratingDist = collect([5, 4, 3, 2, 1])->map(fn ($s) => $ratings->where('rating', $s)->count());

        $noShowMonth = $sessionsAll->where('status', 'no_show')->count();
        $noShowRate = $doneMonth + $noShowMonth > 0 ? round($noShowMonth / ($doneMonth + $noShowMonth) * 100) : 0;

        // Kuota: sesi bulan ini (done+scheduled+no_show konsumsi) vs kapasitas.
        $quotaUsed = $sessionsAll->whereIn('status', ['done', 'scheduled'])
            ->concat($sessionsAll->where('status', 'no_show'))
            ->count();
        $quotaTotal = $registered * $quotaPer;
        $quotaPct = $quotaTotal > 0 ? min(100, round($quotaUsed / $quotaTotal * 100)) : 0;

        // Agenda per hari (7 hari ke depan) — tanpa identitas peserta.
        $agenda = $scheduled->where('scheduled_at', '<=', now()->copy()->addDays(7))
            ->groupBy(fn ($r) => $r->scheduled_at->format('Y-m-d'))
            ->map(fn ($rows, $day) => (object) ['hari' => $day, 'c' => $rows->count()])
            ->sortBy('hari')->values();

        // Chart tren 6 bulan (done per bulan).
        $chartMonthly = ['labels' => [], 'data' => []];
        $monthSeries = $tenant->sessions()->where('status', 'done')
            ->where('scheduled_at', '>=', $start->copy()->subMonths(5))
            ->get()->groupBy(fn ($r) => $r->scheduled_at->format('Y-m'));
        for ($i = 5; $i >= 0; $i--) {
            $m = $start->copy()->subMonths($i);
            $key = $m->format('Y-m');
            $chartMonthly['labels'][] = $m->translatedFormat('M');
            $chartMonthly['data'][] = $monthSeries->get($key)?->count() ?? 0;
        }

        // Sesi per minggu (8 minggu rolling).
        $chartWeekly = ['labels' => [], 'data' => []];
        $weekSeries = $tenant->sessions()->where('status', 'done')
            ->where('scheduled_at', '>=', now()->copy()->subWeeks(8)->startOfWeek())
            ->get()->groupBy(fn ($r) => $r->scheduled_at->copy()->startOfWeek()->format('Y-m-d'));
        for ($i = 7; $i >= 0; $i--) {
            $w = now()->copy()->subWeeks($i)->startOfWeek();
            $chartWeekly['labels'][] = $w->format('d M');
            $chartWeekly['data'][] = $weekSeries->get($w->format('Y-m-d'))?->count() ?? 0;
        }

        // Per departemen / kelas: jumlah peserta done bulan ini (aggregate).
        $groupKey = $school ? 'kelas' : 'departement_id';
        $doneSessions = $sessionsAll->where('status', 'done');
        $grouped = $members->groupBy($groupKey);
        $uniqueByGroup = $grouped->map(fn ($groupMembers) => $doneSessions->whereIn($person, $groupMembers->pluck('id')->all())
            ->pluck($person)->filter()->unique()->count());
        // P0.3: grup dengan contributor nonzero <5 → nilai disuppress (null), label tetap.
        $groupSafe = $grouped->map(fn ($groupMembers, $key) => AggregatePrivacy::safe([
            $uniqueByGroup[$key],
            $doneSessions->whereIn($person, $groupMembers->pluck('id')->all())->count(),
        ]));
        $deptSeries = $grouped->map(function ($groupMembers, $key) use ($doneSessions, $person, $school, $groupSafe) {
            return [
                'name' => $school ? ('Kelas ' . $key) : ($groupMembers->first()->departement?->name ?? 'Tanpa dept'),
                'sessions' => $groupSafe[$key] ? $doneSessions->whereIn($person, $groupMembers->pluck('id')->all())->count() : null,
            ];
        })->values();

        // Partisipasi per departemen (peserta unik done / terdaftar).
        $chartParticipation = [
            'labels' => $deptSeries->pluck('name')->all(),
            'data' => $grouped->map(function ($groupMembers, $key) use ($uniqueByGroup, $groupSafe) {
                if (!$groupSafe[$key]) {
                    return null;
                }
                $unique = $uniqueByGroup[$key];
                return $groupMembers->count() > 0 ? round($unique / $groupMembers->count() * 100) : 0;
            })->values()->all(),
        ];

        // NPS wellbeing = avg WHO-5 terbaru tiap orang (90 hari) skala 100 → /20.
        $assessmentModel = $school ? \App\Models\Sekolah\SekolahAssessment::class : \App\Models\Perusahaan\PerusahaanAssessment::class;
        $assessed = $assessmentModel::where($vertical . '_id', $tenant->id)
            ->where('taken_at', '>=', now()->copy()->subDays(90))->get()
            ->groupBy($person)->map(fn ($rows) => $rows->sortByDesc('taken_at')->first());
        $npsScore = $assessed->isNotEmpty() ? round($assessed->avg('score') / 20, 1) : 0.0;

        $insights = \App\Support\InsightEngine::forAudience(
            $doneSessions->whereNotNull('topic_category')->groupBy('topic_category')->map->count()->all(),
            max(1, $doneSessions->count()),
            $school ? 'school' : 'hr',
            ['risk_high_pct' => $assessed->isNotEmpty() && $assessed->count() >= 5
                ? round($assessed->where('score', '<', 50)->count() / $assessed->count() * 100)
                : null],
        );

        return [
            'registered' => $registered,
            'doneMonth' => $doneMonth,
            'deltaDone' => $doneMonth - $prevDone,
            'scheduled' => $scheduled->count(),
            'scheduledNext7Days' => $scheduledNext7Days,
            'activeMonthPeople' => $activeMonthPeople->count(),
            'activatedTotal' => $activatedPeople->count(),
            'engagementRate' => $registered > 0 ? round($activeMonthPeople->count() / $registered * 100) : 0,
            'quotaUsed' => $quotaUsed,
            'quotaTotal' => $quotaTotal,
            'quotaPct' => $quotaPct,
            'avgRating' => $avgRating,
            'ratingChart' => ['labels' => ['5★', '4★', '3★', '2★', '1★'], 'data' => $ratingDist->all()],
            'noShowRate' => $noShowRate,
            'npsScore' => $npsScore,
            'contractGap' => $contractGap,
            'agenda' => $agenda,
            'chartMonthly' => $chartMonthly,
            'chartWeekly' => $chartWeekly,
            'chartDept' => ['labels' => $deptSeries->pluck('name')->all(), 'data' => $deptSeries->pluck('sessions')->all()],
            'chartParticipation' => $chartParticipation,
            'topParticipationDept' => collect($chartParticipation['labels'])
                ->zip($chartParticipation['data'])->filter(fn ($pair) => $pair[1] !== null)
                ->sortDesc()->first()['name'] ?? null,
            'insights' => $insights,
        ];
    }
}
