@props(['tone' => 'blue'])

<span {{ $attributes->class(['badge', 'badge-muted' => $tone === 'muted']) }}>{{ $slot }}</span>
