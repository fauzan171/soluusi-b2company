<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * A5 — Audit log viewer: list admin_actions read-only + filter
 * (admin, action, target type, rentang tanggal) + pagination 25.
 * Payload TIDAK dirender mentah — mask PII (email → e***@domain,
 * nama → inisial), value panjang di-truncate.
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'admin_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'target_type' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $actions = AdminAction::query()
            ->with('admin:id,firstname,lastname')
            ->when($data['admin_id'] ?? null, fn ($q, $id) => $q->where('admin_id', $id))
            ->when($data['action'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($data['target_type'] ?? null, fn ($q, $t) => $q->where('target_type', $t))
            ->when($data['date_from'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($data['date_to'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', $d . ' 23:59:59'))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $rows = $actions->getCollection()->map(function (AdminAction $a) {
            return [
                'id' => $a->id,
                'admin' => trim(($a->admin?->firstname ?? '') . ' ' . ($a->admin?->lastname ?? '')) ?: '(hapus)',
                'action' => $a->action,
                'target_type' => $a->target_type,
                'target_id' => $a->target_id,
                'payload' => $this->maskPayload($a->payload_json),
                'created_at' => $a->created_at?->format('Y-m-d H:i'),
            ];
        });

        $admins = User::whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->orderBy('firstname')
            ->get(['id', 'firstname', 'lastname']);

        return view('admin.audit-logs.index', [
            'actions' => $actions,
            'rows' => $rows,
            'admins' => $admins,
            'filters' => $data,
        ]);
    }

    /**
     * Mask nilai payload: email → e***@domain, nama → B*** S***,
     * scalar lain → truncate 40 char. Key selalu aman ditampilkan.
     * ponytail: whitelist key kalau nanti ada payload ber-struktur
     * sensitif ber-nested (sekarang payload flat dari record()).
     */
    private function maskPayload(?array $payload): array
    {
        if ($payload === null) {
            return [];
        }

        $out = [];
        foreach ($payload as $key => $value) {
            $out[$key] = is_string($value) ? $this->maskValue($value) : $value;
        }

        return $out;
    }

    private function maskValue(string $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $local = substr($value, 0, strpos($value, '@'));

            return substr($local, 0, 1) . '***' . substr($value, strpos($value, '@'));
        }

        // Nama multi-kata → tiap kata jadi inisial + ***.
        if (preg_match('/^[\p{L}\p{M}\.\'\- ]+$/u', $value) && str_contains(trim($value), ' ')) {
            return implode(' ', array_map(
                fn (string $word) => mb_substr($word, 0, 1) . '***',
                preg_split('/\s+/', trim($value))
            ));
        }

        return mb_substr($value, 0, 40);
    }
}
