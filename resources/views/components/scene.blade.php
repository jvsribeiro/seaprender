@props(['compact' => false])

<div class="absolute inset-x-0 bottom-0" aria-hidden="true">
    <svg @class(['block w-full text-azul-fundo', 'h-10' => $compact, 'h-20' => ! $compact]) viewBox="0 0 1440 120" preserveAspectRatio="xMidYMax slice" shape-rendering="crispEdges">
        <path fill="currentColor" d="M0 120V72h96V48h96V24h144v24h96v24h144V48h96V24h192v24h96v24h144V48h144V24h96v24h96v72z"/>
    </svg>
    <div @class(['ground relative', 'h-6' => $compact, 'h-12' => ! $compact])>
        <x-pixel name="coin" @class(['coin absolute left-[12%]', '-top-8 w-5' => $compact, '-top-12 w-7' => ! $compact]) />
        <x-pixel name="coin" @class(['coin absolute left-[16%] [animation-delay:.2s]', '-top-11 w-5' => $compact, '-top-16 w-7' => ! $compact]) />
        <x-pixel name="coin" @class(['coin absolute left-[20%] [animation-delay:.4s]', '-top-8 w-5' => $compact, '-top-12 w-7' => ! $compact]) />
        <x-pixel name="chest" @class(['absolute right-[10%]', '-top-7 w-9' => $compact, '-top-10 w-12' => ! $compact]) />
    </div>
</div>
