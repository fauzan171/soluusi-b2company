<?php

namespace App\Support;

/**
 * InsightEngine — ubah aggregate topik (COUNT per topic_category) + data pendukung
 * jadi kartu insight "ada case apa → tindakan apa" untuk HR (karyawan) dan
 * guru/wakasek (siswa). Insigth TIDAK pernah menyebut nama individual —
 * hanya topik anonim (UU PDP, spec §6.2 sekolah / §7.1 perusahaan).
 *
 * Dipakai bersama Perusahaan + Sekolah (satu sumber kebenaran rule tindakan).
 */
class InsightEngine
{
    /**
     * Topik → metrik pemantik + tindakan + target pelaksana.
     * key = topic_category konsisten dengan seeder/controller.
     */
    private const TOPIC_RULES = [
        'Stress' => [
            'label' => 'Stress kerja/belajar',
            'threshold' => 0.15,          // >15% dari total sesi
            'insight' => 'Stress jadi topik terbesar — indikasi beban tidak merata.',
            'action_hr' => 'Review beban kerja & deadline mingguan; adakan sesi manajemen stres.',
            'action_school' => 'Kurangi beban tugas & ujian beruntun; koordinasi antar-guru mapel.',
            'owner_hr' => 'HRBP', 'owner_school' => 'Wakasek Kurikulum', 'window' => '2 minggu', 'tone' => 'terra',
        ],
        'Kecemasan' => [
            'label' => 'Kecemasan',
            'threshold' => 0.15,
            'insight' => 'Kecemasan tinggi — butuh edukasi coping, bukan hanya konseling.',
            'action_hr' => 'Sosialisasi teknik pengelolaan cemas (workshop 60 menit) + kanal rujukan.',
            'action_school' => 'Sosialisasi coping skills di jam OS; pastikan siswa tahu cara booking BK.',
            'owner_hr' => 'HR Ops', 'owner_school' => 'Guru BK', 'window' => '7 hari', 'tone' => 'terra',
        ],
        'Burnout' => [
            'label' => 'Burnout',
            'threshold' => 0.10,
            'insight' => 'Ada kasus burnout — tanda kelelahan berkepanjangan.',
            'action_hr' => 'Cek lembur & akumulasi jam kerja; dorong cuti dan recovery.',
            'action_school' => 'Cek jadwal piket & beban administrasi guru; adakan guru wellbeing session.',
            'owner_hr' => 'People Manager', 'owner_school' => 'Kepala Sekolah', 'window' => '1 minggu', 'tone' => 'red',
        ],
        'Karir' => [
            'label' => 'Karin / arah tujuan',
            'threshold' => 0.12,
            'insight' => 'Kebutuhan arah karir tinggi — momen program pengembangan.',
            'action_hr' => 'Buka sesi career coaching + program pengembangan internal.',
            'action_school' => 'Jalankan tes minat-bakat batch untuk kelas akhir + konseling karir.',
            'owner_hr' => 'Talent Dev', 'owner_school' => 'Guru BK / Wali Kelas', 'window' => '1 bulan', 'tone' => 'pine',
        ],
        'Keuangan' => [
            'label' => 'Keuangan',
            'threshold' => 0.12,
            'insight' => 'Masalah keuangan jadi beban — sering tak terlihat di surface.',
            'action_hr' => 'Adakan literasi keuangan + cek program bantuan karyawan.',
            'action_school' => 'Koordinasi beasiswa/subsidi bagi siswa yang membutuhkan (via BK, rahasia).',
            'owner_hr' => 'HR Ops', 'owner_school' => 'Guru BK', 'window' => '1 bulan', 'tone' => 'terra',
        ],
        'Relasi' => [
            'label' => 'Relasi & konflik',
            'threshold' => 0.12,
            'insight' => 'Konflik relasi menonjol — dampak ke dinamika tim/kelas.',
            'action_hr' => 'Facilitasi team building & mediasi konflik di unit terkait.',
            'action_school' => 'Program anti-bullying + pendampingan kelas yang bermasalah.',
            'owner_hr' => 'HRBP', 'owner_school' => 'Wakasek Kesiswaan', 'window' => '2 minggu', 'tone' => 'terra',
        ],
        'Bullying' => [
            'label' => 'Bullying',
            'threshold' => 0.05,          // topik sensitif: threshold rendah
            'insight' => 'Ada indikasi bullying — prioritas penanganan segera.',
            'action_hr' => 'Audit lingkungan kerja & jalur pelaporan (whistleblowing).',
            'action_school' => 'Aktifkan protokol anti-bullying; pendampingan korban via BK + psikolog.',
            'owner_hr' => 'HR Director', 'owner_school' => 'Kepala Sekolah + Guru BK', 'window' => '48 jam', 'tone' => 'red',
        ],
        'Keluarga' => [
            'label' => 'Keluarga',
            'threshold' => 0.12,
            'insight' => 'Beban keluarga memengaruhi kinerja/belajar.',
            'action_hr' => 'Info fleksibilitas kerja & EAP family benefit ke karyawan.',
            'action_school' => 'Adakan parenting class / seminar untuk ortu (koordinasi wali kelas).',
            'owner_hr' => 'HR Ops', 'owner_school' => 'Wakasek Humas / Guru BK', 'window' => '1 bulan', 'tone' => 'pine',
        ],
        'Work-life balance' => [
            'label' => 'Work-life balance',
            'threshold' => 0.12,
            'insight' => 'Work-life balance terkikis — rawan jadi burnout bila dibiarkan.',
            'action_hr' => 'Review jam kerja & budaya lembur; kampanye right to disconnect.',
            'action_school' => 'Review jadwal ekstrakurikuler & tugas non-akademik siswa.',
            'owner_hr' => 'People Manager', 'owner_school' => 'Wakasek Kesiswaan', 'window' => '2 minggu', 'tone' => 'terra',
        ],
        'Percaya diri' => [
            'label' => 'Percaya diri / self-esteem',
            'threshold' => 0.10,
            'insight' => 'Isu kepercayaan diri muncul — umum di fase pengembangan diri.',
            'action_hr' => 'Pelatihan public speaking / mentoring peer antar-karyawan.',
            'action_school' => 'Kegiatan talenta & panggung apresiasi (kelas yang aman untuk berkembang).',
            'owner_hr' => 'Talent Dev', 'owner_school' => 'Guru BK / Pembina Ekskul', 'window' => '1 bulan', 'tone' => 'pine',
        ],
    ];

    /**
     * Bangun daftar insight dari aggregate.
     *
     * @param array $topicCounts  ['Stress' => 12, ...] — sesi done per topik.
     * @param int   $total        total sesi done (denominator threshold).
     * @param string $audience    'hr' (karyawan) | 'school' (guru/wakasek).
     * @param array $extraRisk     flag tambahan: ['risk_high_pct' => ?int, 'no_show_pct' => ?int, 'quota_pct' => ?int].
     * @return array               kartu insight: topic, insight, action, owner, window, tone.
     */
    public static function forAudience(array $topicCounts, int $total, string $audience, array $extraRisk = []): array
    {
        $cards = [];
        $maxCount = max($topicCounts ?: [0]);

        foreach (self::TOPIC_RULES as $topic => $rule) {
            $count = $topicCounts[$topic] ?? 0;
            if ($count === 0) {
                continue;
            }

            $share = $total > 0 ? $count / $total : 0;
            $isDominant = $count === $maxCount;
            // Trigger: melewati threshold ATAU jadi topik terbesar (biar selalu ada 1 insight utama).
            if ($share >= $rule['threshold'] || $isDominant) {
                $cards[] = [
                    'topic' => $rule['label'],
                    'count' => $count,
                    'share' => $total > 0 ? round($share * 100) : 0,
                    'insight' => $isDominant && $share < $rule['threshold']
                        ? "{$rule['label']} jadi topik terbanyak bulan ini — mulai terlihat polanya."
                        : $rule['insight'],
                    'action' => $audience === 'hr' ? $rule['action_hr'] : $rule['action_school'],
                    'owner' => $audience === 'hr' ? $rule['owner_hr'] : $rule['owner_school'],
                    'window' => $rule['window'],
                    'tone' => $rule['tone'],
                ];
            }
        }

        // Urutkan: red dulu, lalu share terbesar.
        usort($cards, fn ($a, $b) => [$a['tone'] !== 'red', $b['share']] <=> [$b['tone'] !== 'red', $a['share']]);

        // Risk flag agregat (WHO-5 band berisiko tinggi).
        $riskPct = $extraRisk['risk_high_pct'] ?? null;
        if ($riskPct !== null && $riskPct >= 10) {
            array_unshift($cards, [
                'topic' => 'Kesejahteraan psikologis',
                'count' => null,
                'share' => $riskPct,
                'insight' => "{$riskPct}% individu terindikasi berisiko tinggi (skrining terakhir).",
                'action' => $audience === 'hr'
                    ? 'Perkuat edukasi & pastikan kanal rujukan krisis sosialisasi ulang.'
                    : 'Koordinasi psikolog + BK untuk follow-up terstruktur (tetap anonim ke pihak sekolah).',
                'owner' => $audience === 'hr' ? 'Wellbeing Lead' : 'Guru BK + Psikolog',
                'window' => '7 hari', 'tone' => 'red',
            ]);
        }

        return $cards;
    }
}
