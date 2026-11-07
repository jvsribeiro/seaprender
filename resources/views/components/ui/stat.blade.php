@props(['label', 'value', 'sprite' => 'star'])

<div class="card flex items-center gap-4 py-4">
    <x-pixel :name="$sprite" class="w-10 shrink-0" />
    <div>
        <p class="font-pixel text-4xl font-bold leading-none text-azul">{{ $value }}</p>
        <p class="mt-1 text-sm font-medium">{{ $label }}</p>
    </div>
</div>
