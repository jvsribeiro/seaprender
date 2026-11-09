@if ($canManage)
    @if ($publication)
        <x-ui.button variant="secondary" :href="route('classrooms.show', $classroom).'#post-'.$publication">Ver publicação no mural</x-ui.button>
    @elseif ($classroom->isActive())
        <x-ui.button variant="secondary" wire:click="publishToMural" wire:loading.attr="disabled" wire:target="publishToMural">Publicar partida no mural</x-ui.button>
    @endif
@endif
