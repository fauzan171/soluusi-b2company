<?php

namespace App\Http\Controllers\Perusahaan;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Services\RosterCsvImport;
use App\Support\PerusahaanContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ONB.2 — import roster karyawan dari CSV.
 *
 * Alur dua langkah: unggah → preview per baris (tidak menulis DB), lalu
 * commit membaca ulang file yang sama. Baris duplikat/error dilewati, bukan
 * menggagalkan batch.
 */
class RosterImportController extends Controller
{
    private const VERTICAL = 'perusahaan';

    public function index()
    {
        return view('perusahaan.roster-import', [
            'perusahaan' => $this->tenant(),
            'preview' => null,
        ]);
    }

    public function preview(Request $request, RosterCsvImport $import)
    {
        $tenant = $this->tenant();
        $path = $this->storeUpload($request, $tenant->id);

        // File CSV berisi password awal dalam bentuk plaintext. Setiap jalur
        // gagal WAJIB menghapusnya — kalau tidak, file menumpuk di disk
        // selamanya pada setiap upload yang tidak jadi di-commit.
        try {
            $rows = $import->read(Storage::disk('local')->path($path));
            $import->assertHeader($rows, self::VERTICAL);
            $preview = $import->preview($rows, self::VERTICAL, $tenant->id);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        $preview['file'] = basename($path);

        // Nama file disimpan di session: satu-satunya sumber untuk commit,
        // jadi HR tidak bisa menunjuk file tenant lain lewat input form.
        session(['import_file' => basename($path)]);

        return view('perusahaan.roster-import', [
            'perusahaan' => $tenant,
            'preview' => $preview,
        ]);
    }

    public function commit(RosterCsvImport $import)
    {
        $tenant = $this->tenant();
        $file = session('import_file');
        abort_unless(is_string($file) && $file !== '', 422, 'Tidak ada file pratinjau. Unggah ulang.');

        $path = $this->importDir($tenant->id) . '/' . basename($file);

        abort_unless(Storage::disk('local')->exists($path), 422, 'File import sudah kedaluwarsa. Unggah ulang.');
        abort_unless(Storage::disk('local')->lastModified($path) > now()->subHour()->timestamp, 422, 'File import kedaluwarsa. Unggah ulang.');

        $rows = $import->read(Storage::disk('local')->path($path));
        // File dihapus SEBELUM commit: kalau commit melempar, password tidak
        // tertinggal di disk. Baris yang sudah dibuat tetap ada (per-baris).
        Storage::disk('local')->delete($path);
        session()->forget('import_file');

        $result = $import->commit($rows, self::VERTICAL, $tenant->id);

        AdminAction::record(auth()->id(), 'roster.import', $tenant, [
            'vertical' => self::VERTICAL,
            'created' => $result['created'],
            'duplicate' => $result['duplicate'],
            'error' => $result['error'],
        ]);

        return redirect()->route('perusahaan.employees')->with('success', sprintf(
            'Import selesai: %d akun dibuat, %d duplikat dilewati, %d baris gagal.',
            $result['created'], $result['duplicate'], $result['error']
        ));
    }

    /** Upload disimpan di storage/app/private/imports/{vertical}-{tenantId}/. */
    private function storeUpload(Request $request, int $tenantId): string
    {
        // 422 langsung (bukan redirect) supaya bentuk respons konsisten dengan
        // validasi header; ini endpoint form upload, bukan API.
        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        abort_unless(strtolower($file->getClientOriginalExtension()) === 'csv', 422, 'File harus berekstensi .csv.');
        abort_if($file->getSize() > RosterCsvImport::MAX_BYTES, 422, 'Ukuran file maksimal 5 MB.');

        $handle = fopen($file->getRealPath(), 'r');
        $lineCount = 0;
        while (fgetcsv($handle) !== false) {
            $lineCount++;
        }
        fclose($handle);
        abort_if($lineCount > RosterCsvImport::MAX_ROWS + 1, 422,
            'Maksimal ' . RosterCsvImport::MAX_ROWS . ' baris per import.');

        $name = $this->importDir($tenantId) . '/' . \Illuminate\Support\Str::uuid() . '.csv';
        Storage::disk('local')->put($name, file_get_contents($file->getRealPath()));

        return $name;
    }

    private function importDir(int $tenantId): string
    {
        return 'private/imports/' . self::VERTICAL . '-' . $tenantId;
    }

    private function tenant()
    {
        $tenant = PerusahaanContext::current();
        abort_unless($tenant, 403, 'Akun tidak terhubung ke perusahaan manapun.');
        abort_unless($tenant->is_active, 403, 'Program belum aktif. Hubungi ops.');

        return $tenant;
    }
}