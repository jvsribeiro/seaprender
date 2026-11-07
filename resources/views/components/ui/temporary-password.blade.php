@props(['password', 'owner'])

<dialog class="modal" x-data x-init="$el.showModal()" aria-label="Senha temporária">
    <h2 class="font-marca text-xl font-semibold text-azul">Senha temporária</h2>
    <p class="mt-2">Passe esta senha para {{ $owner }}. Ela aparece só agora, e a troca é pedida no próximo login.</p>
    <p class="ticket mt-3 select-all">{{ $password }}</p>
    <div class="mt-5 flex justify-end">
        <x-ui.button wire:click="clearTemporaryPassword">Fechar</x-ui.button>
    </div>
</dialog>
