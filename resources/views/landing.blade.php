<x-layout :title="$tab === 'login' ? 'Entrar | Se Aprender' : 'Se Aprender | Toda aula é uma fase nova'">
    <x-auth-scene>
        <h2 id="titulo-acesso" class="sr-only">{{ $tab === 'login' ? 'Entrar' : 'Criar conta' }}</h2>

        <nav class="flex gap-1" aria-label="Acesso">
            <a class="auth-tab" href="{{ route('home') }}" @if ($tab === 'register') aria-current="page" @endif>Criar conta</a>
            <a class="auth-tab" href="{{ route('login') }}" @if ($tab === 'login') aria-current="page" @endif>Entrar</a>
        </nav>

        @if (session('status'))
            <p class="mt-4 bg-creme p-3 text-sm font-medium" role="status">{{ session('status') }}</p>
        @endif

        @if ($tab === 'login')
            <form method="POST" action="{{ route('login.store') }}" class="mt-6 grid gap-4">
                @csrf
                <x-field name="email" label="E-mail" type="email" autocomplete="username" />
                <x-field name="password" label="Senha" type="password" autocomplete="current-password" />
                <div class="flex flex-wrap items-center justify-between gap-2 text-sm font-medium">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="remember" class="size-4 accent-azul">
                        Manter conectado
                    </label>
                    <a class="text-azul underline underline-offset-2" href="{{ route('password.request') }}">Esqueci a senha</a>
                </div>
                <button type="submit" class="btn-px mt-1 w-full">Entrar</button>
            </form>
        @else
            <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-4">
                @csrf
                <x-field name="name" label="Nome" autocomplete="name" />
                <x-field name="email" label="E-mail" type="email" autocomplete="email" />
                <x-field name="password" label="Senha" type="password" autocomplete="new-password" hint="Pelo menos 8 caracteres." />
                <x-field name="password_confirmation" label="Confirme a senha" type="password" autocomplete="new-password" />
                <button type="submit" class="btn-px mt-1 w-full">Criar conta</button>
            </form>
        @endif
    </x-auth-scene>
</x-layout>
