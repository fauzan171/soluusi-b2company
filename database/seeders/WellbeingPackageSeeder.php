<?php

namespace Database\Seeders;

use App\Models\WellbeingPackage;
use App\Models\WellbeingPricingBand;
use Illuminate\Database\Seeder;

/**
 * Katalog Wellbeing (K3, DATA — editable runtime via admin UI, T3). 4 paket +
 * 5 band × 3 paket berbayar = 15 band rows (PRODUCT-DOC-SUMMARY §1–2; band
 * table menang utk angka MHCU, resolusi §8.2/8.3). Idempotent: firstOrCreate
 * per code / (package, band_min). TIDAK dipanggil dari DatabaseSeeder.
 */
class WellbeingPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'code' => 'discovery', 'name' => 'Discovery',
                'duration_days' => 14, 'is_free' => true,
                'assessment_count' => 1, 'assessment_max_participants' => 40,
                'assessment_domains' => ['mh', 'wellbeing'], // WHO-5 / DASS-21
                'expert_chat_experts' => 0, 'expert_chat_quota' => 0,
                'deep_support_quota' => 8, // 8 sesi psikolog utk 8 karyawan terpilih (K12)
                'deep_support_mode' => 'one_on_one',
                'webinar_count' => 0, 'quarterly_review' => false,
                'is_active' => true,
            ],
            [
                'code' => 'foundation', 'name' => 'Foundation',
                'duration_days' => 90, 'is_free' => false,
                'assessment_count' => 1, 'assessment_max_participants' => null,
                'assessment_domains' => ['mh'], // 1 domain (MH atau Nutrition)
                'expert_chat_experts' => 1, 'expert_chat_quota' => 0, // kuota chat diset saat assign (T4)
                'deep_support_quota' => 0, 'deep_support_mode' => null, // tidak termasuk
                'webinar_count' => 1, 'quarterly_review' => false,
                'is_active' => true,
            ],
            [
                'code' => 'growth', 'name' => 'Growth',
                'duration_days' => 180, 'is_free' => false,
                'assessment_count' => 2, 'assessment_max_participants' => null,
                'assessment_domains' => ['mh', 'nutrition'],
                'expert_chat_experts' => 2, 'expert_chat_quota' => 0,
                'deep_support_quota' => 15, 'deep_support_mode' => 'mhcu', // default option A; XOR per kontrak (K7)
                'webinar_count' => 3, 'quarterly_review' => false,
                'is_active' => true,
            ],
            [
                'code' => 'impact', 'name' => 'Impact',
                'duration_days' => 365, 'is_free' => false,
                'assessment_count' => 2, 'assessment_max_participants' => null,
                'assessment_domains' => ['mh', 'nutrition'],
                'expert_chat_experts' => 2, 'expert_chat_quota' => 0,
                'deep_support_quota' => 30, 'deep_support_mode' => 'mhcu',
                'webinar_count' => 6, 'quarterly_review' => true,
                'is_active' => true,
            ],
        ];

        foreach ($packages as $attrs) {
            WellbeingPackage::firstOrCreate(['code' => $attrs['code']], $attrs);
        }

        // Harga Rp/bulan per band (exclude pajak, K13) + MHCU quota per band
        // (band table menang atas default katalog — PRODUCT-DOC-SUMMARY §2).
        $bands = [
            // [band_min, band_max, foundation, growth, impact, mhcu_growth, mhcu_impact]
            [50, 150, 7999999, 7499999, 6999999, 5, 10],
            [151, 400, 14999999, 14499999, 13999999, 10, 20],
            [401, 600, 20999999, 20499999, 19999999, 15, 30],
            [601, 800, 25999999, 25499999, 24999999, 20, 40],
            [801, 1000, 30999999, 30499999, 29999999, 25, 50],
        ];

        $byCode = WellbeingPackage::whereIn('code', ['foundation', 'growth', 'impact'])->get()->keyBy('code');

        foreach ($bands as [$min, $max, $fPrice, $gPrice, $iPrice, $gMhcu, $iMhcu]) {
            foreach ([
                ['foundation', $fPrice, null], // foundation: no deep support → no MHCU
                ['growth', $gPrice, $gMhcu],
                ['impact', $iPrice, $iMhcu],
            ] as [$code, $price, $mhcu]) {
                WellbeingPricingBand::firstOrCreate(
                    ['wellbeing_package_id' => $byCode[$code]->id, 'band_min' => $min],
                    ['band_max' => $max, 'monthly_price' => $price, 'mhcu_quota' => $mhcu],
                );
            }
        }
    }
}
