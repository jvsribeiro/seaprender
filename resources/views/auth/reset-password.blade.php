<x-layout title="Nova senha | Se Aprender">
    <x-auth-scene>
        <h2 id="titulo-acesso" class="font-marca text-2xl font-semibold text-azul">Nova senha</h2>

        <form method="POST" action="{{ route('password.update') }}" class="mt-5 grid gap-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div>
                <label for="email" class="mb-1 block text-sm font-semibold">E-mail</label>
                <input id="email" name="email" type="email" class="field-input" autocomplete="username" required value="{{ old('email', $request->email) }}">
                @error('email')
                    <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p>
                @enderror
            </div>
            <x-field name="password" label="Nova senha" type="password" autocomplete="new-password" hint="Pelo menos 8 caracteres." />
            <x-field name="password_confirmation" label="Confirme a senha" type="password" autocomplete="new-password" />
            <button type="submit" class="btn-px mt-1 w-full">Salvar senha</button>
        </form>
    </x-auth-scene>
</x-layout>
