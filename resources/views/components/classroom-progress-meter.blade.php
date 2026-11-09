@props(['student', 'totals'])

@php
    $total = $totals['quiz'] + $totals['reflection'];
    $completed = min($total, $student->quiz_count + $student->reflection_count);
@endphp

<div {{ $attributes->class('mt-1 grid max-w-52 gap-0.5') }}>
    @if ($total > 0)
        <progress class="h-2 w-full accent-azul" value="{{ $completed }}" max="{{ $total }}" aria-label="Progresso de {{ $student->name }}"></progress>
        <span class="text-xs font-medium text-noite/75">{{ $completed }} de {{ $total }} participações concluídas</span>
    @else
        <span class="text-xs font-medium text-noite/75">Sem participações disponíveis</span>
    @endif
</div>
