@extends('layouts.app')

@php
    $title = 'Privasi & Consent';
@endphp

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <h1 class="text-xl font-bold">Privasi &amp; Consent</h1>
        <p class="text-sm text-gray-500">Status consent program dan apa yang bisa dilihat HR.</p>
    </div>

    <div class="dash-card p-5 mb-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-[0.95rem] font-semibold text-gray-900">Status consent program</h2>
                <p class="mt-1 text-xs text-gray-500">Consent program Wellbeing diatur oleh HR Anda (dengan bukti persetujuan terdokumentasi).</p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $employee->consent_at ? 'bg-pine-100 text-pine-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $employee->consent_at ? 'Consent aktif sejak '.optional($employee->consent_at)->format('d M Y') : 'Belum ada consent' }}
            </span>
        </div>
        <p class="mt-3 text-xs text-gray-400">Untuk mengubah status consent, hubungi HR {{ $perusahaan?->brand ?? 'perusahaan Anda' }} — perubahan dicatat dengan bukti persetujuan (audit trail).</p>
    </div>

    <div class="dash-card p-5 mb-4">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Apa yang HR bisa lihat</h2>
        <ul class="space-y-2 text-sm text-gray-600">
            <li class="flex gap-2"><span aria-hidden="true">•</span> <span><strong>Agregat</strong> tingkat kesejahteraan perusahaan (distribusi risiko, partisipasi) — hanya jika minimal 10 responden; kelompok kecil disembunyikan.</span></li>
            <li class="flex gap-2"><span aria-hidden="true">•</span> <span>Status administratif Anda: keanggotaan program, status consent, dan jumlah sesi/asesmen (bukan isi).</span></li>
            <li class="flex gap-2"><span aria-hidden="true">•</span> <span>Skor asesmen Anda <strong>tidak</strong> pernah muncul di dashboard HR — HR melihat agregat band, bukan skor individual.</span></li>
        </ul>
    </div>

    <div class="dash-card p-5 mb-4">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Apa yang HR tidak bisa lihat</h2>
        <ul class="space-y-2 text-sm text-gray-600">
            <li class="flex gap-2"><span aria-hidden="true">•</span> <span>Isi chat konsultasi Anda dengan expert (retensi transkrip 90 hari, lalu dihapus otomatis).</span></li>
            <li class="flex gap-2"><span aria-hidden="true">•</span> <span>Jawaban asesmen individual dan detail item.</span></li>
            <li class="flex gap-2"><span aria-hidden="true">•</span> <span>Identitas Anda dalam agregat apa pun di bawah 10 responden.</span></li>
        </ul>
    </div>

    <div class="dash-card p-5">
        <h2 class="text-[0.95rem] font-semibold text-gray-900 mb-3">Riwayat consent</h2>
        @if($consents->isEmpty())
            <p class="text-sm text-gray-400">Belum ada riwayat consent tercatat.</p>
        @else
            <div class="space-y-2">
                @foreach($consents as $c)
                <div class="panel-surface bg-white flex items-center justify-between text-sm">
                    <div class="min-w-0">
                        <p class="text-gray-700">{{ $c->granted ? 'Consent diberikan' : 'Consent dicabut' }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $c->evidence }}</p>
                    </div>
                    <span class="text-xs text-gray-400 shrink-0">{{ optional($c->agreed_at)->format('d M Y') }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
