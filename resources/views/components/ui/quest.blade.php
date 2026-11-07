@props(['title', 'steps'])

@php
    $doneCount = collect($steps)->where('done', true)->count();
@endphp

<x-ui.card :title="$title">
    <p class="mt-1 text-sm">{{ $doneCount }} de {{ count($steps) }} concluídos</p>
    <div class="mt-2 flex gap-1" aria-hidden="true">
        @foreach ($steps as $step)
            <span @class(['quest-cell', 'quest-cell-done' => $step['done']])></span>
        @endforeach
    </div>
    <ol class="mt-3 grid gap-2">
        @foreach ($steps as $step)
            <li class="flex items-center gap-2 font-medium">
                <span @class(['quest-check', 'quest-check-done' => $step['done']]) aria-hidden="true">
                    @if ($step['done'])
                        <x-pixel name="check" class="w-3.5" :palette="['w' => 'currentColor']" />
                    @endif
                </span>
                {{ $step['label'] }}
                @if ($step['done'])
                    <span class="sr-only">(concluído)</span>
                @endif
            </li>
        @endforeach
    </ol>
</x-ui.card>
