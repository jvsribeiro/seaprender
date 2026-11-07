@props(['code', 'title', 'text'])

<x-layout :title="$title.' | Se Aprender'">
    <main class="relative z-10 mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-4 pb-32 pt-12 sm:px-6">
        <div><span class="plaque"><x-logo class="h-10 w-auto" /></span></div>
        <p class="mt-10 font-pixel text-7xl font-bold text-laranja sm:text-8xl">{{ $code }}</p>
        <h1 class="mt-3 font-marca text-3xl font-semibold">{{ $title }}</h1>
        <p class="mt-2 text-lg text-white/90">{{ $text }}</p>
        <div class="mt-7">
            <a class="btn-px" href="{{ url('/') }}">Voltar ao início</a>
        </div>
    </main>
    <x-scene />
</x-layout>
