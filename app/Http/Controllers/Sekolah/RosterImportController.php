<?php

namespace App\Http\Controllers\Sekolah;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Services\RosterCsvImport;
use App\Support\SekolahContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * ONB.6 — import roster sekolah dari CSV (varian siswa + staf).
 *
 * Cermin RosterImportController perusahaan: unggah → preview → commit,
 * file pratinjau hanya bisa dipakai tenant pemiliknya.
 */
class RosterImportController extends Controller
{
    private const VERTICAL = 'sekolah';

    public function index()
    {
        return view('sekolah.roster-import', [
            'sekolah' => $this->tenant(),
            'preview' => null,
        ]);
    }

    public function preview(Request $request, RosterCsvImport $import)
    {
        $tenant = $this->tenant();
        $path = $this->storeUpload($request, $tenant->id);

        // File CSV berisi password awal dalam bentuk plaintext. Setiap jalur
        // gagal WAJIB menghapusnya — kalau tidak, file menumpuk di disk.
        try {
            $rows = $import->read(Storage::disk('local')->path($path));
            $import->assertHeader($rows, self::VERTICAL);
            $preview = $import->preview($rows, self::VERTICAL, $tenant->id);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        $preview['file'] = basename($path);
        // Key per vertikal: superadmin yang bolak-balik preview dua portal
        // tidak saling menimpa file pratinjau.
        session(['import_file_' . self::VERTICAL => basename($path)]);

        return view('sekolah.roster-import', [
            'sekolah' => $tenant,
            'preview' => $preview,
        ]);
    }

    public function commit(RosterCsvImport $import)
    {
        $tenant = $this->tenant();
        $file = session('import_file_' . self::VERTICAL);
        abort_unless(is_string($file) && $file !== '', 422, 'Tidak ada file pratinjau. Unggah ulang.');

        $path = $this->importDir($tenant->id) . '/' . basename($file);

        abort_unless(Storage::disk('local')->exists($path), 422, 'File import sudah kedaluwarsa. Unggah ulang.');
        abort_unless(Storage::disk('local')->lastModified($path) > now()->subHour()->timestamp, 422, 'File import kedaluwarsa. Unggah ulang.');

        $rows = $import->read(Storage::disk('local')->path($path));
        // Hapus SEBELUM commit: password plaintext tidak tertinggal di disk.
        Storage::disk('local')->delete($path);
        session()->forget('import_file_' . self::VERTICAL);

        $result = $import->commit($rows, self::VERTICAL, $tenant->id);

        AdminAction::record(auth()->id(), 'roster.import', $tenant, [
            'vertical' => self::VERTICAL,
            'created' => $result['created'],
            'created_staff' => $result['created_staff'] ?? 0,
            'duplicate' => $result['duplicate'],
            'error' => $result['error'],
        ]);

        return redirect()->route('sekolah.students')->with('success', sprintf(
            'Import selesai: %d akun dibuat (%d staf), %d duplikat dilewati, %d baris gagal.',
            $result['created'], $result['created_staff'] ?? 0, $result['duplicate'], $result['error']
        ));
    }

    /** Upload disimpan di storage/app/private/imports/sekolah-{tenantId}/. */
    private function storeUpload(Request $request, int $tenantId): string
    {
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
        $tenant = SekolahContext::current();
        abort_unless($tenant, 403, 'Akun tidak terhubung ke sekolah manapun.');
        abort_unless($tenant->is_active, 403, 'Program belum aktif. Hubungi ops.');

        return $tenant;
    }
}
