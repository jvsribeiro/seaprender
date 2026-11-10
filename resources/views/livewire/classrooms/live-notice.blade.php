<div @if ($classroom->isActive()) wire:poll.5s.visible @endif>
    @if ($room)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-2 border-azul bg-creme p-3 sm:p-4">
            <div class="flex min-w-0 items-center gap-3">
                <x-pixel name="trophy" class="w-9 shrink-0" />
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wider text-azul">{{ $room->isQuestionGame() ? 'Se Pergunta' : 'Quiz ao vivo' }}</p>
                    <p class="wrap-anywhere font-marca text-lg font-semibold">{{ $room->title }}</p>
                </div>
            </div>
            <x-ui.button :href="route('quizzes.live', $room)">{{ $canManage ? 'Abrir sala' : 'Entrar na partida' }}</x-ui.button>
        </div>
    @endif
</div>
