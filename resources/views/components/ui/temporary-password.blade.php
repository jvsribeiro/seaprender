@props(['password', 'owner'])

{{-- Closing with Esc or the back gesture also clears the password, so the next one opens a fresh dialog. --}}
<dialog class="modal" x-data x-init="$el.showModal()" x-on:close="$wire.clearTemporaryPassword()" aria-label="Senha temporária">
    <h2 class="font-marca text-xl font-semibold text-azul">Senha temporária</h2>
    <p class="mt-2">Passe esta senha para {{ $owner }}. Ela aparece só agora, e a troca é pedida no próximo login.</p>
    <p class="ticket mt-3 select-all">{{ $password }}</p>
    <div class="mt-5 flex justify-end">
        <x-ui.button x-on:click="$el.closest('dialog').close()">Fechar</x-ui.button>
    </div>
</dialog>
