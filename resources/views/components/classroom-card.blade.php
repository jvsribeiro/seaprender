@props(['classroom'])

<a href="{{ route('classrooms.show', $classroom) }}" wire:navigate class="card card-link block p-0">
    <x-classroom-cover :classroom="$classroom" />
    <div class="p-4">
        <div class="flex items-start justify-between gap-3">
            <h3 class="min-w-0 wrap-anywhere font-marca text-lg font-semibold text-azul">{{ $classroom->name }}</h3>
            <x-ui.badge :tone="$classroom->isActive() ? 'blue' : 'muted'" class="shrink-0">{{ $classroom->status->label() }}</x-ui.badge>
        </div>
        <p class="mt-1 text-sm">{{ $classroom->curricular_unit }} · {{ $classroom->period }}</p>
        <p class="mt-3 text-sm font-medium">{{ $classroom->active_enrollments_count }} {{ $classroom->active_enrollments_count === 1 ? 'aluno' : 'alunos' }} · {{ $classroom->teacher->name }}</p>
    </div>
</a>
