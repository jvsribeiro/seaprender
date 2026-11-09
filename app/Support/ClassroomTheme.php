<?php

namespace App\Support;

final class ClassroomTheme
{
    public const PRESETS = [
        'classic' => ['label' => 'Pixel clássico', 'base' => '#004C94', 'accent' => '#F7941D', 'pattern' => 'grid'],
        'orbit' => ['label' => 'Órbita roxa', 'base' => '#422275', 'accent' => '#CE9CFF', 'pattern' => 'stars'],
        'forest' => ['label' => 'Bosque', 'base' => '#115B46', 'accent' => '#86E4A2', 'pattern' => 'dots'],
        'sunset' => ['label' => 'Pôr do sol', 'base' => '#8B314C', 'accent' => '#FFAE70', 'pattern' => 'stripes'],
        'ocean' => ['label' => 'Mar profundo', 'base' => '#075773', 'accent' => '#79E1EB', 'pattern' => 'waves'],
    ];

    public const PATTERNS = [
        'grid' => ['label' => 'Grade pixel', 'sprite' => 'book'],
        'stars' => ['label' => 'Constelação', 'sprite' => 'star'],
        'dots' => ['label' => 'Pontilhado', 'sprite' => 'coin'],
        'stripes' => ['label' => 'Faixas diagonais', 'sprite' => 'trophy'],
        'waves' => ['label' => 'Ondas', 'sprite' => 'chest'],
        'plain' => ['label' => 'Liso', 'sprite' => 'scroll'],
    ];

    private const COLOR_PATTERN = '/\A#[0-9a-f]{6}\z/i';

    public const COLOR_RULE = 'regex:'.self::COLOR_PATTERN;

    /** @return array{palette: string, label: string, base: string, accent: string, pattern: string, sprite: string} */
    public static function resolve(?string $palette, ?string $pattern = null, ?string $base = null, ?string $accent = null): array
    {
        $preset = self::PRESETS[$palette] ?? self::PRESETS['classic'];
        $custom = $palette === 'custom';
        $pattern = isset(self::PATTERNS[$pattern]) ? $pattern : $preset['pattern'];

        return [
            'palette' => $custom || isset(self::PRESETS[$palette]) ? $palette : 'classic',
            'label' => $custom ? 'Personalizado' : $preset['label'],
            'base' => $custom && $base !== null && preg_match(self::COLOR_PATTERN, $base) ? $base : $preset['base'],
            'accent' => $custom && $accent !== null && preg_match(self::COLOR_PATTERN, $accent) ? $accent : $preset['accent'],
            'pattern' => $pattern,
            'sprite' => self::PATTERNS[$pattern]['sprite'],
        ];
    }
}
