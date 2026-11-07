@props(['name', 'palette' => []])

{!! \App\Support\PixelArt::svg($name, $attributes->get('class', ''), $palette) !!}
