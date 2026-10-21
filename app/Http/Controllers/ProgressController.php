<?php

namespace App\Http\Controllers;

use App\Services\Screening\ScoringService;
use Illuminate\Support\Facades\DB;

/**
 * Halaman status pengembangan — untuk presentasi. Menampilkan angka NYATA dari DB
 * dan menjalankan mesin skor langsung, bukan mockup.
 */
class ProgressController extends Controller
{
    public function index()
    {
        $questions = DB::table('screening_questions')->orderBy('ord')->get();
        $bySection = $questions->groupBy('section')->map->count();

        // Demo mesin skor: satu set jawaban contoh per domain, dihitung pada soal asli.
        $samples = [
            'mental_health' => ['Q5' => 3, 'Q6' => 2, 'Q7' => 3, 'Q8' => 1, 'Q9' => 2,
                'Q10' => 3, 'Q11' => 2, 'Q12' => 2, 'Q13' => 3],
            'nutrition_lifestyle' => ['Q15' => 1, 'Q16' => 2, 'Q17' => 1, 'Q18' => 2, 'Q19' => 1, 'Q20' => 0],
            'ergonomic' => ['Q21' => 2, 'Q22' => 3, 'Q23' => 2, 'Q24' => 1, 'Q25' => 3,
                'Q26' => 0, 'Q27' => 1, 'Q28' => 2, 'Q31' => 7, 'Q33' => 3],
        ];

        $results = [];
        foreach ($samples as $section => $answers) {
            $qs = $questions->where('section', $section)->where('vertical', 'perusahaan')->values();
            $scored = ScoringService::scoreSection($section, $answers, $qs);
            $band = ScoringService::bandFor($scored['score']);
            $results[$section] = $scored + [
                'band' => $band['color'],
                'band_label' => ScoringService::colorLabel($band['color']),
                'consult' => $band['consult'],
            ];
        }
        $recommend = ScoringService::recommend(array_map(fn ($r) => $r['score'], $results));

        $stats = [
            'questions_per_vertical' => $questions->where('vertical', 'perusahaan')->count(),
            'questions_total' => $questions->count(),
            'perusahaan' => DB::table('perusahaans')->count(),
            'employees' => DB::table('perusahaan_employees')->count(),
            'employees_consent' => DB::table('perusahaan_employees')->whereNotNull('consent_at')->count(),
            'sessions' => DB::table('perusahaan_sessions')->count(),
            'students' => DB::table('sekolah_students')->count(),
            'users' => DB::table('users')->count(),
        ];

        return view('progress', compact('bySection', 'results', 'recommend', 'stats', 'questions'));
    }
}