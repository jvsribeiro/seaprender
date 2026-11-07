@props(['title', 'action', 'label' => 'Confirmar'])

<div x-data class="contents">
    <span class="contents" x-on:click="$refs.dialog.showModal()">{{ $trigger }}</span>

    <dialog x-ref="dialog" class="modal" aria-label="{{ $title }}">
        <h2 class="font-marca text-xl font-semibold text-azul">{{ $title }}</h2>
        @if ($slot->isNotEmpty())
            <p class="mt-2">{{ $slot }}</p>
        @endif
        <div class="mt-5 flex flex-wrap justify-end gap-3">
            <x-ui.button variant="secondary" x-on:click="$refs.dialog.close()">Cancelar</x-ui.button>
            <x-ui.button wire:click="{{ $action }}" x-on:click="$refs.dialog.close()">{{ $label }}</x-ui.button>
        </div>
    </dialog>
</div>
