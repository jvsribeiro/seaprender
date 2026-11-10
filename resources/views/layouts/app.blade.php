<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? "{$title} | Se Aprender" : 'Se Aprender' }}</title>
    <meta name="theme-color" content="#004C94">
    <link rel="icon" href="{{ asset('images/logo-icon.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-bg font-sans text-noite antialiased">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:absolute focus:left-3 focus:top-3 focus:z-50 focus:bg-white focus:p-3 focus:font-semibold">Pular para o conteúdo</a>

    <div class="min-h-dvh lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]" x-data="{ menu: false }">
        <header class="flex items-center justify-between border-b-4 border-noite bg-white px-4 py-2 lg:hidden">
            <a href="{{ route('dashboard') }}" wire:navigate aria-label="Se Aprender, painel"><x-logo class="h-9 w-auto" /></a>
            <button type="button" class="btn-quiet text-azul" x-on:click="menu = !menu" x-bind:aria-expanded="menu" aria-controls="menu-principal">Menu</button>
        </header>

        <aside id="menu-principal" class="hidden flex-col bg-azul text-white lg:sticky lg:top-0 lg:flex lg:h-dvh" x-bind:class="menu && 'flex!'">
            <a href="{{ route('dashboard') }}" wire:navigate class="hidden border-b-4 border-noite bg-white px-5 py-4 lg:block" aria-label="Se Aprender, painel"><x-logo class="h-auto w-full" /></a>

            <nav class="grid gap-1 p-3" aria-label="Principal">
                <x-ui.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" sprite="home">Painel</x-ui.nav-link>
                <x-ui.nav-link :href="route('classrooms.index')" :active="request()->routeIs('classrooms.*', 'quizzes.*', 'question-games.*') && ! request()->routeIs('classrooms.browse')" sprite="book">Turmas</x-ui.nav-link>
                @if (auth()->user()->isStudent())
                    <x-ui.nav-link :href="route('classrooms.browse')" :active="request()->routeIs('classrooms.browse')" sprite="lens">Procurar turmas</x-ui.nav-link>
                @endif
                @can('administer')
                    <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-[0.14em] text-pessego">Administração</p>
                    <x-ui.nav-link :href="route('admin.users')" :active="request()->routeIs('admin.users')" sprite="users">Usuários</x-ui.nav-link>
                    <x-ui.nav-link :href="route('admin.audit')" :active="request()->routeIs('admin.audit')" sprite="scroll">Auditoria</x-ui.nav-link>
                @endcan
            </nav>

            <div class="mt-3 flex items-center gap-3 border-t-2 border-white/20 p-4 lg:mt-auto">
                <x-ui.avatar :name="auth()->user()->name" class="avatar-light" />
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-white/80">{{ auth()->user()->role->label() }}</p>
                    @isset($studentProgress)
                        <div class="my-2 grid gap-1">
                            <p class="flex flex-wrap items-baseline justify-between gap-x-2 text-xs font-semibold">
                                <span>Progresso geral</span>
                                <span>{{ $studentProgress['completed'] }} de {{ $studentProgress['total'] }}</span>
                            </p>
                            <progress class="block h-2 w-full accent-pessego" value="{{ $studentProgress['completed'] }}" max="{{ max(1, $studentProgress['total']) }}" aria-label="Progresso geral de quizzes e reflexões" aria-valuetext="{{ $studentProgress['completed'] }} de {{ $studentProgress['total'] }} participações concluídas"></progress>
                        </div>
                    @endisset
                    <div class="mt-1 flex items-center gap-3">
                        <a class="btn-quiet text-sm" href="{{ route('password.change') }}" wire:navigate>Trocar senha</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-quiet text-sm">Sair</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <main id="conteudo" class="w-full min-w-0 px-4 py-6 sm:px-8 sm:py-8">
            <div class="mx-auto w-full max-w-7xl">{{ $slot }}</div>
        </main>
    </div>

    <x-ui.toasts />
</body>
</html>
