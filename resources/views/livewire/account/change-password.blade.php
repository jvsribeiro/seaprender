<div>
    <x-ui.page-header title="Trocar senha" :subtitle="$mustChange ? 'Crie uma senha sua para continuar.' : null" />

    <form wire:submit="save" class="card grid max-w-md gap-4">
        @unless ($mustChange)
            <x-ui.input name="current_password" label="Senha atual" type="password" autocomplete="current-password" />
        @endunless
        <x-ui.input name="password" label="Nova senha" type="password" autocomplete="new-password" />
        <x-ui.input name="password_confirmation" label="Confirme a nova senha" type="password" autocomplete="new-password" />
        <div>
            <x-ui.button type="submit">Salvar senha</x-ui.button>
        </div>
    </form>
</div>
