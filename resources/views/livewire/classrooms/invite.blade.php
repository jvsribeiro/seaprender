<div class="w-full min-w-0 pt-8">
    <div class="card text-center">
        @if ($classroom)
            <x-pixel name="star" class="mx-auto w-12" :palette="['o' => '#004C94']" />
            <h1 class="mt-4 break-words font-marca text-2xl font-semibold text-azul">{{ $classroom->name }}</h1>
            <p class="mt-1">{{ $classroom->curricular_unit }} · {{ $classroom->teacher->name }}</p>
            <div class="mt-5">
                @php($status = $classroom->enrollments->first()?->status)
                @if ($status === \App\Enums\EnrollmentStatus::Active)
                    <x-ui.button :href="route('classrooms.show', $classroom)">Abrir turma</x-ui.button>
                @elseif ($status === \App\Enums\EnrollmentStatus::Pending)
                    <x-ui.badge tone="muted">Aguardando aprovação</x-ui.badge>
                @elseif ($status === \App\Enums\EnrollmentStatus::Rejected || $status === \App\Enums\EnrollmentStatus::Removed)
                    <p class="text-sm">Fale com o professor sobre a sua inscrição.</p>
                @else
                    <p class="mb-3 text-sm">Seu acesso será liberado quando o professor aprovar a inscrição.</p>
                    <x-ui.button wire:click="join" wire:loading.attr="disabled">Solicitar inscrição</x-ui.button>
                @endif
            </div>
            @error('code')
                <p class="mt-3 text-sm font-medium text-erro">{{ $message }}</p>
            @enderror
        @else
            <x-ui.empty title="Convite inválido">
                Peça um novo código ao professor.
            </x-ui.empty>
        @endif
    </div>
</div>
