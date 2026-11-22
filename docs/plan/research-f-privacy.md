# Research F — Health Data Privacy (PDP Indonesia)

> Ditulis mandor langsung (research agent mati 2x oleh API 503; topik terselesaikan dari primary sources yang di-fetch manual + praktik standar). Kalimat tanpa URL = praktik umum industri, tandai [conv].

## 1. UU PDP 27/2022 — kewajiban praktis untuk app ini

- Health data = **data pribadi spesifik** (Pasal 4 jo. 21-24): pemrosesan butuh **consent eksplisit** ATAU legal basis lain (kewajiban hukum/kontrak). App: consent karyawan sudah ada (`consent_at` di `perusahaan_employees`) — pertahankan, audit trail-nya.
- **Pasal 35-36 (keamanan)**: prosesor wajib langkah teknis+organisatoris — enkripsi, kontrol akses, audit.
- **Pasal 46 (breach notification)**: insiden wajib dilaporkan (3x24 jam ke subjek+lembaga, per PP pelaksana) → app minimal: error log + alerting, prosedur tertulis.
- **Pasal 56 (cross-border transfer)**: data kesehatan keluar Indonesia butuh verifikasi; VPS di Indonesia = hindari masalah; JANGAN pakai SaaS chat US/EU (pusher/ably) → mendukung K11 (self-host polling). [conv untuk detail pasal — verifikasi hukum final oleh owner/counsel]
- Sumber: https://peraturan.bpk.go.id/Details/242467/uu-no-27-tahun-2022 (official JDIH BPK)

## 2. Role-based access + field encryption

- Pola RBAC: policy per-model (HR: aggregate only; consultant: own sessions only; employee: own results). Laravel Policies/Gate = native fit — codify yang sekarang ad-hoc abort_*.
- **Field-level encryption**: Laravel `encrypted` cast (AES-256-CBC, key dari APP_KEY). Cukup untuk at-rest kolom sensitif di sqlite (file-level). Manajemen kunci: keep APP_KEY aman (backup owner, rotasi = re-encrypt kolom — dokumentasikan sebagai prosedur, jangan auto). [conv]
- Upgrade path kalau butuh kompartemen lebih kuat: kolom `encryptable` per-model + key terpisah per tenant — YAGNI untuk skala ini. [conv]

## 3. Audit logging

- **spatie/laravel-activitylog** = de-facto standar (event model + custom log, DB table, robust) vs custom audit table: pilih custom audit table MUNGKIN karena dependency baru harus diminimal (composer + prod install terbatas). **Keputusan: custom `audit_logs` table minimal** (actor, action, subject_type/id, meta JSON, ip) — dipicu dari event penting saja (akses catatan klinis, export, refund kuota, perubahan flag). [conv, validasi saat W7]
- Yang wajib di-log: akses konsultan ke notes sesi (bukan isi — event aksesnya), HR aggregate view (paket query besar), export data, perubahan role/assignment, refund ledger event.

## 4. Threshold agregasi n≥5

- n≥5 = **k-anonymity sederhana** — standar pragmatis workplace reporting ( banyak corporate wellness dashboard pakai 5-10; HIPAA Safe Harbor pakai 16-populasi, tapi itu identifikasi medis record, bukan agregat voluntary program). n≥5 untuk populasi perusahaan kecil = trade-off sensibel; alternatif differential privacy = overkill + susah dijelaskan ke HR. **Keep n≥5** (AggregatePrivacy::safe() existing). [conv]

## 5. Retensi

- Rekam medis formal Indonesia: PMK 269/2008 — RS wajib simpan rekam medis **minimal 10 tahun** sejak kunjungan terakhir. Konseling psikolog B2B ≠ rekam medis RS secara hukum ketat, tapi prinsip "clinical record lama lebih aman disimpan" berlaku untuk notes (litigasi/kontinuitas perawatan).
- **Keputusan**: `consultation_notes` + `consultation_results` = **no auto-purge** (retensi panjang, sesuai konteks klinis); `chat_messages` = purge 90 hari post-session (Prunable) — chat = channel koordinasi, bukan catatan klinis formal. Perusahaan bisa minta data-deletion saat kontrak berakhir (proses manual superadmin, log ke audit).
- Sumber: https://peraturan.bpk.go.id/Details/108977/pp-no-47-tahun-2016 & PMK 269/2008 via JDIH (direferensikan, detail pasal diverifikasi owner bila dipakai formal).

## Rekomendasi untuk mission (ringkas)

1. encrypted cast: `consultation_notes.body`, `chat_messages.body` (baru), `consultation_results` optional.
2. audit_logs minimal custom table (W7) — spatie = opsional upgrade nanti.
3. n≥5 dipertahankan.
4. Retensi: notes permanent, chat 90 hari (Prunable command di scheduler — hidupkan scheduler!).
5. Chat tetap self-host polling (K11) — konsisten PDP cross-border avoidance.
