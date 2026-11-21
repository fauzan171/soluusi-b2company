<?php

namespace Database\Seeders;

use App\Models\Consultant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dummy konsultan B2C (calon import dari soluusi.com) — katalog HR.
 * ~24 profil: user + consultant + kategori + skill + pendidikan + sertifikat.
 * Idempoten: skip per-email.
 */
class PerusahaanConsultantCatalogSeeder extends Seeder
{
    /** [nama, kode kategori, kategori label, tahun pengalaman, about] */
    private array $profiles = [
        ['Dr. Anisa Rahmawati, M.Psi., Psikolog', 'psikolog-klinis', 'Psikolog Klinis', 11, 'Psikolog klinis senior dengan fokus trauma, PTSD, dan kecemasan kerja. Pendekatan CBT dan EMDR terintegrasi.'],
        ['Bagus Setiawan, S.Psi., M.Psi.', 'psikolog-klinis', 'Psikolog Klinis', 7, 'Praktik depresi, burnout, dan dinamika tempat kerja. Sering menangani karyawan shift dan frontline.'],
        ['Citra Dewi Anggraini, M.Psi., Psikolog', 'psikolog-klinis', 'Psikolog Klinis', 9, 'Intervensi kecemasan sosial dan panik. Terapis berorientasi solusi jangka pendek untuk profesional muda.'],
        ['Hendra Gunawan, S.Psi., M.Psi.', 'psikolog-klinis', 'Psikolog Klinis', 6, 'Fokus manajemen stres eksekutif dan insomnia kerja. Menggabungkan psikoterapi dan higiene tidur.'],
        ['Dr. Ratna Sari, M.Psi., Psikolog', 'psikolog-klinis', 'Psikolog Klinis Anak & Remaja', 12, 'Spesialis remaja: tekanan akademik, school refusal, dan self-harm. Kolaborator sekolah aktif.'],
        ['Yoga Pratama, S.Psi.', 'psikolog-pendidikan', 'Psikolog Pendidikan', 5, 'Asesmen kesulitan belajar dan konseling karier pelajar. Pendekatan terstruktur berbasis data.'],
        ['Melati Kusumaningrum, S.Psi.', 'psikolog-pendidikan', 'Psikolog Pendidikan', 8, 'Konselor pendidikan untuk SMP–SMA; parenting coaching untuk orang tua bekerja.'],
        ['Dr. Wisnu Wardhana, M.Psi., Psikolog', 'konselor', 'Konselor Keluarga & Pernikahan', 14, 'Konselor keluarga sistemik: konflik pasangan dua karier, komunikasi generasi, dan parenting.'],
        ['Sekar Ayu Lestari, M.Psi.', 'konselor', 'Konselor Keluarga & Pernikahan', 9, 'Terapis relasi dengan pendekatan emotionally focused therapy (EFT). 800+ jam sesi.'],
        ['Fajar Alfarizi, S.Psi.', 'konselor', 'Konselor Keluarga & Pernikahan', 4, 'Konselor lulusan baru dengan energi tinggi; fokus pra-nikah dan komunikasi konflik.'],
        [' dr. Lindawati Soetanto, S.Gz, RD', 'ahli-gizi', 'Nutrisionis Klinis', 13, 'Dietitian diabetes, dislipidemia, dan gizi karyawan kantor. Program gizi korporat 15+ perusahaan.'],
        ['Reza Maulana Ibrahim, S.Gz, RD', 'ahli-gizi', 'Nutrisionis Klinis', 6, 'Program weight management realistis untuk pekerja shift; meal plan kantin sehat.'],
        ['Tiara Putri Handayani, S.Gz', 'ahli-gizi', 'Nutrisionis Komunitas', 5, 'Edukasi gizi komunitas dan kantin sekolah; kreator konten gizi berbasis bukti.'],
        ['Dimas Anggara Nugraha, S.Gz, RD', 'ahli-gizi', 'Nutrisionis Klinis', 8, 'Gizi olahraga dan hydrasi pekerja lapangan. Konsultan corporate wellness program.'],
        ['Arif Wicaksono, S.Ft., Ftr.', 'fisioterapis', 'Fisioterapis Muskuloskeletal', 10, 'Fisioterapi nyeri leher/punggung pekerja komputer dan ergonomi workstation.'],
        ['Bella Safitri, S.Ft., Ftr.', 'fisioterapis', 'Fisioterapis Muskuloskeletal', 6, 'Rehabilitasi cedera olahraga karyawan; program peregangan kantor 30 menit.'],
        ['Gilang Ramadhan, S.Ft., Ftr.', 'fisioterapis', 'Fisioterapis Muskuloskeletal', 7, 'Manual therapy untuk nyeri punggung kronis; edukasi postur kerja data-driven.'],
        ['Hana Maulida, S.Ft., Ftr.', 'fisioterapis', 'Fisioterapis Muskuloskeletal', 5, 'Fisioterapis pasca-operasi ortopedi dan return-to-work assessment.'],
        ['Rina Hartono, M.Psi., CHRP', 'karir_b2b', 'Konselor Karir', 10, 'Konselor karir korporat: career transition, outplacement, dan coaching kinerja.'],
        ['Dedi Kurniawan, S.T., M.M.', 'karir_b2b', 'Konselor Karir', 12, 'Career coach dari industri manufaktur; asesmen minat bakat dan planning karir.'],
        ['Maya Zahra Alifa, S.Psi.', 'karir_b2b', 'Konselor Karir', 6, 'Coaching fresh graduate dan onboarding career; asisten CV dan simulasi wawancara.'],
        ['Nurul Aini Hakim, S.Psi., M.Psi.', 'psikolog-pendidikan', 'Psikolog Pendidikan', 7, 'Psikolog pendidikan untuk kesiapan sekolah dan konseling remaja karir.'],
        ['Aditya Pramudya, S.Psi., M.Psi.', 'psikolog-klinis', 'Psikolog Klinis', 8, 'Terapis kecemasan kesehatan dan OCD; paparan bertahap terukur (ERP).'],
        ['Vania Larissa, S.Psi.', 'psikolog-pendidikan', 'Psikolog Pendidikan', 4, 'Konselor pendidikan muda; fokus motivasi belajar dan regulasi emosi pelajar.'],
    ];

    private array $skillPool = ['Counseling', 'Anxiety', 'Psychotherapy', 'Mental Health', 'Stress Management', 'Work-life Balance', 'Psychological Assessment', 'Career Coaching', 'Nutrition Planning', 'Ergonomics', 'Family Therapy', 'Mindfulness'];

    public function run(): void
    {
        $now = now();
        $consultantRoleId = DB::table('roles')->where('name', 'consultant')->value('id');
        $catIds = DB::table('categories')->whereIn('vertical', ['klinik', 'both'])->pluck('id', 'code_category');
        $skillIds = collect();

        foreach ($this->profiles as $idx => [$name, $catCode, $catLabel, $years, $about]) {
            $name = trim($name);
            $username = Str::slug($name) . '-b2c' . $idx;
            $email = $username . '@b2c-soluusi.local';

            if (DB::table('users')->where('email', $email)->exists()) {
                continue; // idempoten
            }

            $userId = DB::table('users')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'username' => $username,
                'email' => $email,
                'password' => bcrypt('password'),
                'firstname' => explode(' ', $name)[0],
                'lastname' => implode(' ', array_slice(explode(' ', $name), 1)),
                'role_id' => $consultantRoleId,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $consultantId = DB::table('consultants')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'about' => $about,
                'status' => 'accept',
                'is_active' => 1,
                'profit_sharing' => 80,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($catId = $catIds->get($catCode)) {
                DB::table('consultant_categories')->insert([
                    'consultant_id' => $consultantId,
                    'category_id' => $catId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2-4 skill acak.
            foreach (collect($this->skillPool)->shuffle()->take(rand(2, 4)) as $skillName) {
                $skillId = $skillIds->get($skillName) ?: DB::table('skills')->where('name', $skillName)->value('id')
                    ?: DB::table('skills')->insertGetId(['name' => $skillName, 'created_at' => $now, 'updated_at' => $now]);
                $skillIds->put($skillName, $skillId);
                DB::table('consultant_skills')->insert([
                    'consultant_id' => $consultantId,
                    'skill_id' => $skillId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $univs = ['Universitas Indonesia', 'Universitas Gadjah Mada', 'Universitas Airlangga', 'Universitas Padjadjaran'];
            $educationId = DB::table('education')->where('name', $univs[array_rand($univs)])->value('id')
                ?: DB::table('education')->insertGetId(['name' => $univs[array_rand($univs)], 'created_at' => $now, 'updated_at' => $now]);
            $gradYear = now()->year - $years - 4;
            DB::table('consultant_education')->insert([
                'consultant_id' => $consultantId,
                'education_id' => $educationId,
                'degree' => 'S2 Psikologi Profesi',
                'start_date' => ($gradYear - 2) . '-08-01',
                'end_date' => $gradYear . '-07-01',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('certificates')->insert([
                'name' => $catLabel === 'Fisioterapis' ? 'Sertifikasi Fisioterapi Lanjutan' : 'Sertifikasi Praktik Profesional',
                'number' => 'CERT-' . strtoupper(Str::random(8)),
                'year' => min(now()->year, $gradYear + rand(1, 4)),
                'created_at' => $now,
                'updated_at' => $now,
                'consultant_id' => $consultantId,
            ]);

            // Dummy rating agregat (consultant_ratings) — 5-40 review per konsultan.
            // user_id NOT NULL di skema — pakai admin demo (id=1) sebagai rater placeholder.
            $adminId = DB::table('users')->where('email', 'admin@klinik-konsultan.local')->value('id') ?? 1;
            foreach (range(1, rand(5, 40)) as $i) {
                DB::table('consultant_ratings')->insert([
                    'consultant_id' => $consultantId,
                    'user_id' => $adminId,
                    'rating' => collect([5, 5, 5, 5, 4, 4, 4, 3])->random(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
