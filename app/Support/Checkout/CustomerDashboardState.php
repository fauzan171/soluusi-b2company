<?php

namespace App\Support\Checkout;

use App\Models\ConsultationSession;
use App\Models\Klinik\CheckoutLink;

/**
 * T4.2: state-machine-in-array untuk white-label status page customer.
 * Murni view-state mapping — tidak ada query, tidak ada side effect.
 */
class CustomerDashboardState
{
    public static function for(CheckoutLink $link, ?ConsultationSession $session): array
    {
        if (!$session) {
            return [
                'title' => 'Belum checkout',
                'subtitle' => $link->isUsable()
                    ? 'Link aktif. Lengkapi data untuk mengunci jadwal konsultasi.'
                    : 'Link belum dipakai dan sudah tidak aktif. Minta link baru ke klinik.',
                'tone' => $link->isUsable() ? 'amber' : 'red',
                'paymentLabel' => 'Belum dibuat',
                'sessionLabel' => 'Belum booking',
                'nextAction' => $link->isUsable() ? 'Isi data dan bayar untuk mengamankan jadwal.' : 'Hubungi klinik untuk link baru.',
                'progress' => 1,
            ];
        }

        if ($session->payment_status === ConsultationSession::PAYMENT_STATUS_WAITING) {
            return [
                'title' => 'Menunggu pembayaran',
                'subtitle' => 'Sesi sudah dibuat sementara. Jadwal terkunci sampai batas waktu pembayaran.',
                'tone' => 'amber',
                'paymentLabel' => 'Menunggu pembayaran',
                'sessionLabel' => 'Belum terjadwal final',
                'nextAction' => 'Lanjutkan pembayaran agar jadwal dikonfirmasi.',
                'progress' => 2,
            ];
        }

        if (in_array($session->payment_status, [
            ConsultationSession::PAYMENT_STATUS_CANCELED,
            ConsultationSession::PAYMENT_STATUS_FAILED,
            ConsultationSession::PAYMENT_STATUS_REFUNDED,
        ], true) || $session->status === ConsultationSession::STATUS_CANCELED) {
            $refunded = $session->payment_status === ConsultationSession::PAYMENT_STATUS_REFUNDED;
            $failed = $session->payment_status === ConsultationSession::PAYMENT_STATUS_FAILED;
            return [
                'title' => $refunded ? 'Pembayaran dikembalikan' : ($failed ? 'Pembayaran gagal' : 'Sesi dibatalkan'),
                'subtitle' => $refunded ? 'Dana sudah dikembalikan ke metode pembayaranmu.' : 'Sesi ini tidak aktif untuk konsultasi.',
                'tone' => 'red',
                'paymentLabel' => $refunded ? 'Dana dikembalikan' : ($failed ? 'Gagal' : 'Dibatalkan'),
                'sessionLabel' => 'Tidak aktif',
                'nextAction' => 'Hubungi klinik untuk membuat jadwal atau link baru.',
                'progress' => 1,
            ];
        }

        if ($session->completion_verified_at) {
            return [
                'title' => 'Sesi selesai',
                'subtitle' => 'Konsultasi sudah selesai dan terkonfirmasi.',
                'tone' => 'green',
                'paymentLabel' => 'Terverifikasi',
                'sessionLabel' => 'Selesai',
                'nextAction' => 'Tidak ada aksi lanjutan untuk sesi ini.',
                'progress' => 4,
            ];
        }

        if ($session->konsultan_done_at) {
            return [
                'title' => 'Menunggu konfirmasi kamu',
                'subtitle' => 'Konsultan sudah menandai sesi selesai. Konfirmasi jika sesi benar sudah berlangsung.',
                'tone' => 'blue',
                'paymentLabel' => 'Terverifikasi',
                'sessionLabel' => 'Menunggu konfirmasi',
                'nextAction' => 'Konfirmasi sesi selesai setelah konsultasi berlangsung.',
                'progress' => 3,
            ];
        }

        if ($session->payment_status === ConsultationSession::PAYMENT_STATUS_VERIFIED) {
            return [
                'title' => 'Sesi terjadwal',
                'subtitle' => 'Pembayaran sudah diterima. Simpan jadwal konsultasi kamu.',
                'tone' => 'green',
                'paymentLabel' => 'Terverifikasi',
                'sessionLabel' => 'Terjadwal',
                'nextAction' => 'Datang sesuai jadwal. Link meeting akan muncul jika sesi online sudah disiapkan.',
                'progress' => 3,
            ];
        }

        return [
            'title' => 'Status sedang diproses',
            'subtitle' => 'Data sesi sedang diperbarui.',
            'tone' => 'slate',
            'paymentLabel' => ucfirst(str_replace('_', ' ', $session->payment_status)),
            'sessionLabel' => ucfirst(str_replace('_', ' ', $session->status)),
            'nextAction' => 'Muat ulang halaman ini beberapa saat lagi.',
            'progress' => 2,
        ];
    }
}
