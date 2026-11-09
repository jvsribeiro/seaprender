<div class="relative flex flex-1 flex-col">
    <x-pixel name="cloud" class="cloud absolute left-[4%] top-24 hidden w-28 opacity-80 lg:block" />
    <x-pixel name="cloud" class="cloud absolute left-[38%] top-10 hidden w-20 opacity-60 lg:block" />

    <header class="relative z-10 w-full px-4 py-5 sm:px-6 lg:px-12">
        <a href="{{ route('home') }}" class="plaque" aria-label="Se Aprender, início"><x-logo class="h-10 w-auto sm:h-12" /></a>
    </header>

    <main class="relative z-10 grid w-full flex-1 items-center gap-7 px-4 pb-32 pt-2 sm:px-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] lg:gap-16 lg:px-12 lg:pb-28">
        <div>
            <h1 class="font-pixel text-[2.6rem] font-bold leading-[0.98] sm:text-7xl lg:text-[5.2rem]">
                Toda aula é uma <span class="text-laranja">fase nova.</span>
            </h1>
            <p class="mt-4 max-w-md text-lg leading-snug text-white/90 sm:text-xl sm:leading-relaxed">
                Atividades, entregas e quizzes ao vivo para a sua turma, com XP, moedas e conquistas.
            </p>
        </div>

        <section class="px-frame bg-white p-5 text-noite sm:p-7" aria-labelledby="titulo-acesso">
            {{ $slot }}
        </section>
    </main>

    <x-scene />
</div>
