<?php

namespace App\Services;

use App\Support\AggregatePrivacy;

class ProgramAggregate
{
    public static function monthly($tenant, string $vertical): array
    {
        // R10: report perusahaan pakai threshold min 10 (re-identifikasi data
        // kesehatan mental); sekolah tetap 5 (legacy baseline).
        $min = $vertical === "perusahaan" ? 10 : 5;
        [$month, $start, $end] = AggregatePrivacy::month();
        $school = $vertical === 'sekolah';
        $person = $school ? 'student_id' : 'employee_id';
        $roster = $school ? $tenant->students() : $tenant->employees();
        $assessmentModel = $school ? \App\Models\Sekolah\SekolahAssessment::class : \App\Models\Perusahaan\PerusahaanAssessment::class;
        $sessions = $tenant->sessions()->where('scheduled_at', '>=', $start)->where('scheduled_at', '<', $end)->get();
        $done = $sessions->where('status', 'done');
        $assessed = $assessmentModel::where('instrument_code', 'who5')->where($vertical.'_id', $tenant->id)->where('taken_at', '>=', $start)
            ->where('taken_at', '<', $end)->when($school, fn ($q) => $q->studentRoster())->get()
            ->groupBy($person)->map(fn ($rows) => $rows->sortByDesc('taken_at')->first());
        $risk = [
            'Sehat' => $assessed->where('score', '>=', 75)->count(),
            'Perlu Perhatian' => $assessed->filter(fn ($a) => $a->score >= 50 && $a->score < 75)->count(),
            'Berisiko Tinggi' => $assessed->where('score', '<', 50)->count(),
        ];
        $people = fn ($rows) => $rows->pluck($person)->filter()->unique()->count();
        $topics = $done->whereNotNull('topic_category')->groupBy('topic_category');
        $types = $done->groupBy('konsultan_type');
        $ratings = $done->whereNotNull('rating')->groupBy('rating');
        $statuses = $sessions->groupBy('status');
        $days = $sessions->groupBy(fn ($row) => $row->scheduled_at->format('Y-m-d'));
        $members = $roster->get();
        $groupKey = $school ? 'kelas' : 'departement_id';
        $groups = $members->groupBy($groupKey)->map(function ($members, $key) use ($done, $person, $people, $school, $min) {
            $rows = $done->whereIn($person, $members->pluck('id')->all());
            $n = $people($rows);
            // P0.3: suppress per-group — departemen/kelas kecil bisa re-identifikasi.
            $groupSafe = AggregatePrivacy::safe([$n, $rows->count()], $min);
            return [
                'name' => $school ? (string) $key : ($members->first()->departement?->name ?? 'Tanpa departemen'),
                'registered' => $members->count(),
                'participants' => $groupSafe ? $n : null,
                'sessions' => $groupSafe ? $rows->count() : null,
            ];
        })->values();
        // ponytail: conservative whole-family suppression; narrower release needs disclosure review.
        $safe = AggregatePrivacy::safe($statuses->map($people), $min)
            && AggregatePrivacy::safe($topics->map($people), $min)
            && AggregatePrivacy::safe($types->map($people), $min)
            && AggregatePrivacy::safe($ratings->map($people), $min)
            && AggregatePrivacy::safe($days->map($people), $min)
            && AggregatePrivacy::safe($groups->pluck('participants'), $min);
        $riskSafe = AggregatePrivacy::safe($risk, $min) && ($assessed->isEmpty() || $assessed->count() >= 5);
        return [
            'month' => $month, 'start' => $start, 'registered' => $members->count(),
            'suppressed' => !$safe, 'riskSuppressed' => !$riskSafe,
            'done' => $safe ? $done->count() : null,
            'participants' => $safe ? $people($done) : null,
            'noShow' => $safe ? $sessions->where('status', 'no_show')->count() : null,
            'participation' => $safe && $members->count() ? round($people($done) / $members->count() * 100) : null,
            'avgRating' => $safe && $ratings->isNotEmpty() ? round($done->whereNotNull('rating')->avg('rating'), 1) : null,
            'groups' => $safe ? $groups : collect(),
            'topics' => $safe ? $topics->map->count() : collect(),
            'byType' => $safe ? $types->map->count() : collect(),
            'riskDist' => $riskSafe ? $risk : null,
            'totalAssessed' => $riskSafe ? $assessed->count() : null,
            'insights' => $safe ? \App\Support\InsightEngine::forAudience($topics->map->count()->all(), max(1, $done->count()), $school ? 'school' : 'hr', []) : [],
        ];
    }
}
