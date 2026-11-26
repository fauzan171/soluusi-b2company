@extends('layouts.app')

@php($title = $instrument->name)
@php($likertMax = $items->max(fn ($i) => $i->type === 'bool' ? 1 : (int) $i->weight))

@section('content')
<div class="max-w-3xl" data-assessment-wizard>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-bold">{{ $instrument->name }}</h1>
            <p class="text-sm text-gray-500">{{ $items->count() }} pertanyaan. {{ $instrument->interpretation }}</p>
        </div>
        <a href="{{ route('perusahaan.assessment.instruments') }}" class="text-sm text-pine-700 hover:underline">← Pilih instrumen lain</a>
    </div>

    <form method="POST" action="{{ route('perusahaan.assessment.instrument.store', $instrument->code) }}" class="space-y-4">
        @csrf

        {{-- Progress bar + counter (wizard shell; tanpa JS tetap dirender, statis). --}}
        <div data-progress class="portal-card p-4">
            <div class="flex items-center justify-between text-xs font-semibold text-gray-500 mb-2">
                <span data-progress-label>0/{{ $items->count() }} terjawab</span>
                <span data-progress-pct>0%</span>
            </div>
            <div class="h-2 rounded-full bg-[#e7e2d9] overflow-hidden">
                <div data-progress-bar class="h-full rounded-full bg-pine-700 w-0 transition-all"></div>
            </div>
        </div>

        @foreach($items as $i => $item)
        <div class="portal-card p-4" data-step>
            @if($item->type === 'forced-choice')
            <p class="text-sm font-medium mb-3">{{ $i + 1 }}. Pilih pernyataan yang paling cocok dan paling tidak cocok dengan Anda.</p>
            @foreach(['p' => 'Paling cocok', 'k' => 'Paling tidak cocok'] as $suffix => $groupLabel)
            <p class="text-xs font-semibold text-gray-500 mt-2 mb-1">{{ $groupLabel }}</p>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach($item->options ?? [] as $val => $label)
                <label class="flex items-center gap-2 text-sm border border-[#e7e2d9] rounded-lg px-3 py-2 cursor-pointer hover:border-pine-600 has-checked:border-pine-600 has-checked:bg-pine-50">
                    <input type="radio" name="answers[{{ $item->code.$suffix }}]" value="{{ $val }}" required class="accent-pine-700">
                    <span class="leading-snug">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @endforeach
            @error("answers.{$item->code}p")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @error("answers.{$item->code}k")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @else
            <p class="text-sm font-medium mb-3">{{ $i + 1 }}. {{ $item->text }}</p>
            {{-- Likert buttons: label pilihan jadi tombol besar sejajar. --}}
            <div class="grid {{ count($item->options ?? []) > 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }} gap-2">
                @foreach($item->options ?? [] as $val => $label)
                <label class="flex items-center justify-center gap-2 text-sm border border-[#e7e2d9] rounded-lg px-3 py-3 cursor-pointer text-center hover:border-pine-600 hover:bg-pine-50/50 has-checked:border-pine-600 has-checked:bg-pine-50 has-checked:font-semibold">
                    <input type="radio" name="answers[{{ $item->code }}]" value="{{ $val }}" required class="sr-only accent-pine-700">
                    <span class="leading-snug">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error("answers.{$item->code}")<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            @endif
        </div>
        @endforeach

        {{-- Step terakhir: topik diskusi opsional utk konsultan (disimpan ke notes). --}}
        <div class="portal-card p-4" data-step data-step-optional>
            <p class="text-sm font-medium mb-2">Apa yang ingin kamu diskusikan dengan konsultan?</p>
            <p class="text-xs text-gray-500 mb-3">Opsional. Topik ini membantu konsultan menyiapkan sesi — hanya kamu dan konsultan yang melihatnya.</p>
            <textarea name="discussion_note" rows="3" maxlength="1000"
                      class="w-full border border-[#e7e2d9] rounded-lg px-3 py-2 text-sm focus:border-pine-600 focus:outline-none"
                      placeholder="Contoh: ingin bicara soal pola tidur atau manajemen beban kerja…">{{ old('discussion_note') }}</textarea>
            @error('discussion_note')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
            Ini skrining awal, <b>bukan diagnosis</b>. Jika skor rendah atau kamu sedang dalam kesulitan, hubungi konsultan lewat fitur booking atau hotline krisis <b>119 ext. 8</b>.
        </div>

        {{-- Nav wizard (JS menampilkan; tanpa JS semua pertanyaan tampil + submit biasa). --}}
        <div data-wizard-nav class="hidden flex items-center justify-between gap-3">
            <button type="button" data-nav-prev
                    class="portal-card text-sm font-semibold text-gray-600 rounded px-4 py-2.5 disabled:opacity-40 disabled:cursor-not-allowed">Sebelumnya</button>
            <span data-nav-counter class="text-xs font-semibold text-gray-400"></span>
            <button type="button" data-nav-next
                    class="bg-pine-700 text-white text-sm font-semibold rounded px-6 py-2.5 hover:bg-pine-800">Lanjut</button>
        </div>

        <button type="submit" data-submit
                class="w-full bg-pine-700 text-white text-sm font-semibold rounded px-4 py-2.5 hover:bg-pine-800">Simpan Hasil</button>
    </form>
</div>

<script>
(function () {
    var root = document.querySelector('[data-assessment-wizard]');
    if (!root) return;

    var steps = Array.prototype.slice.call(root.querySelectorAll('[data-step]'));
    var nav = root.querySelector('[data-wizard-nav]');
    if (!nav || steps.length < 2) return;

    var submit = root.querySelector('[data-submit]');
    var prev = root.querySelector('[data-nav-prev]');
    var next = root.querySelector('[data-nav-next]');
    var counter = root.querySelector('[data-nav-counter]');
    var bar = root.querySelector('[data-progress-bar]');
    var pct = root.querySelector('[data-progress-pct]');
    var label = root.querySelector('[data-progress-label]');
    var total = steps.length;
    var current = 0;

    // Semua input jawaban (radio) — utk hitung terjawab.
    var answerInputs = Array.prototype.slice.call(
        root.querySelectorAll('input[type="radio"][name^="answers["]')
    );
    var answerGroups = {};
    answerInputs.forEach(function (el) {
        answerGroups[el.name] = answerGroups[el.name] || [];
        answerGroups[el.name].push(el);
        el.addEventListener('change', updateProgress);
    });
    var groupNames = Object.keys(answerGroups);

    nav.classList.remove('hidden');
    nav.classList.add('flex');
    submit.classList.add('hidden');

    function answeredCount() {
        return groupNames.filter(function (name) {
            return answerGroups[name].some(function (el) { return el.checked; });
        }).length;
    }

    function updateProgress() {
        var answered = answeredCount();
        var fraction = groupNames.length ? answered / groupNames.length : 0;
        if (bar) bar.style.width = Math.round(fraction * 100) + '%';
        if (pct) pct.textContent = Math.round(fraction * 100) + '%';
        if (label) label.textContent = answered + '/' + groupNames.length + ' terjawab';
    }

    function render() {
        steps.forEach(function (step, idx) {
            step.style.display = idx === current ? '' : 'none';
        });
        prev.disabled = current === 0;
        // Step terakhir (topik opsional) → tombol jadi "Selesai".
        next.textContent = current === total - 1 ? 'Selesai' : 'Lanjut';
        if (counter) counter.textContent = (current + 1) + ' / ' + total;
        if (current === total - 1) {
            submit.classList.remove('hidden');
            next.classList.add('hidden');
        } else {
            submit.classList.add('hidden');
            next.classList.remove('hidden');
        }
    }

    function gotoNextUnanswered(from) {
        // lompat ke step pertama setelah `from` yang belum terjawab (lewati opsional).
        for (var i = from + 1; i < total - 1; i++) {
            var inputs = steps[i].querySelectorAll('input[type="radio"][name^="answers["]');
            var answered = Array.prototype.some.call(inputs, function (el) { return el.checked; });
            if (!answered) return i;
        }
        return total - 1;
    }

    prev.addEventListener('click', function () {
        if (current > 0) { current--; render(); }
    });
    next.addEventListener('click', function () {
        current = gotoNextUnanswered(current);
        render();
    });

    render();
    updateProgress();
})();
</script>
@endsection
