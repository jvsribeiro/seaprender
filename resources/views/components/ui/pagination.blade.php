@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Paginação">
        <x-ui.button variant="secondary" wire:click="previousPage('{{ $paginator->getPageName() }}')" :disabled="$paginator->onFirstPage()">Anterior</x-ui.button>
        <p class="text-sm font-medium">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</p>
        <x-ui.button variant="secondary" wire:click="nextPage('{{ $paginator->getPageName() }}')" :disabled="! $paginator->hasMorePages()">Próxima</x-ui.button>
    </nav>
@endif
