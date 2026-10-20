<?php

namespace Database\Seeders;

use FFI;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define categories. B2C preserved verbatim (merge parity).
        $categories = [
            'Psikologi',
            'Karir',
            'Bisnis',
            'Keuangan',
            'Hukum',
            'Pajak'
        ];

        $categoriesId = [];
        foreach ($categories as $category) {
            $categoriesId[$category] = \App\Models\Category::create([
                'code_category' => strtolower($category),
                'name' => $category,
                'vertical' => 'b2c',
            ])->id;
        }

        // B2B §15.13: extend kategori kesehatan klinik. Category-A instruments
        // (MMCI/MMPI) disabled di MVP (§15.5 NEEDS FOUNDER) — ini cuma kategori.
        $klinikCategories = [
            'Psikolog Klinis',
            'Psikolog Pendidikan',
            'Konselor',
            'Ahli Gizi',
            'Fisioterapis',
            'Terapis Okupasi',
        ];

        foreach ($klinikCategories as $category) {
            \App\Models\Category::create([
                'code_category' => strtolower(str_replace(' ', '-', $category)),
                'name' => $category,
                'vertical' => 'klinik',
            ]);
        }


        // Define skills and their corresponding categories
        $skills = [
            'Psikologi' => [
                'Anxiety', 'Emotional', 'Psychological Assessment', 'Psychotherapy', 'Counseling',
                'Psychotherapy in Transpersonal and Humanistic Approach', 'Clinical Psychologist',
                'Mental Health', 'Clinical Psychology', 'Assessment & Training', 'Personality Test',
                'Emotional', 'Self-Compassion', 'Self Development', 'Tumbuh Kembang Anak dan Remaja',
                'Minat, bakat dan karir', 'Psikologis Klinis', 'Educational Psychologist',
                'Children and Teenagers Mental Health', 'Family Counseling', 'Relationship Consultant',
                'Trauma', 'Manajemen Stress', 'Manajemen Waktu', 'Relationship Counseling', 'Self Harm',
                'Depression', 'Emotional Management', 'Kids and Family Consulting', 'Self Development',
                'Therapist', 'Psikologi Pendidikan', 'Trauma Healing', 'Body Image'
            ],
            'Karir' => [
                'Human Resource', 'Psikologi Industri dan Organisasi', 'Career Plans & Development',
                'Training & Coaching', 'Konsuling', 'Recruiter', 'Competency Assessment',
                'Organizational Development', 'Minat & Bakat', 'Talent Mapping', 'Vocational Guidance',
                'Self Development', 'Career', 'Worklife', 'Analisa Jabatan dan Beban Kerja', 'Burnout',
                'Interviewing'
            ],
            'Bisnis' => [
                'Training & Coaching', 'Capital Budgeting', 'Strategi Bisnis', 'Marketing',
                'Komunikasi', 'Kopi Konsultan', 'Bisnis Makanan', 'Manajemen Restoran',
                'Hospitality Management', 'Recruiting', 'Competency Assessment', 'Organizational Development',
                'Talent Mapping', 'Researcher', 'Bisnis Kreatif', 'Entrepreneurship', 'Talent Acquisition Specialist',
                'Perkembangan Bisnis', 'Branding', 'Social Media Marketing', 'Recruiter', 'Creative Entrepreneur',
                'Fashion Business', 'Interviewing', 'Marketing Specialist'
            ],
            'Keuangan' => [
                'Equity Research', 'Financial Planning', 'Wealth Planner', 'Finance', 'Financial Planner',
                'Qualified Wealth Planner', 'Syria Finance', 'Produk Investasi', 'Accounting',
                'Fundamental Analysis', 'Qualified Wealth Planner', 'Taxation', 'Personal Finance',
                'Financial Influencer'
            ],
            'Hukum' => [
                'Hak Kekayaan Intelektual', 'Penyelesaian Sengketa', 'Pendirian Badan Usaha',
                'Hukum Keluarga', 'Hukum Ketenagakerjaan', 'Hukum KPBU'
            ],
            'Pajak' => ['Akuntasi']
        ];

        foreach ($skills as $categoryName => $skillArray) {
            foreach ($skillArray as $skillName) {
                $skill = \App\Models\Skill::create([
                    'name' => $skillName
                ]);
                $skill->categories()->attach($categoriesId[$categoryName]);
            }
        }
    }
}
