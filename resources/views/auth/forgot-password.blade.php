<x-layout title="Recuperar senha | Se Aprender">
    <x-auth-scene>
        <h2 id="titulo-acesso" class="font-marca text-2xl font-semibold text-azul">Recuperar senha</h2>
        <p class="mt-1 text-sm">Enviamos um link para o seu e-mail.</p>

        @if (session('status'))
            <p class="mt-4 bg-creme p-3 text-sm font-medium" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-5 grid gap-4">
            @csrf
            <x-field name="email" label="E-mail" type="email" autocomplete="username" />
            <button type="submit" class="btn-px mt-1 w-full">Enviar link</button>
        </form>

        <a class="mt-4 inline-block text-sm font-medium text-azul underline underline-offset-2" href="{{ route('login') }}">Voltar para o login</a>
    </x-auth-scene>
</x-layout>
