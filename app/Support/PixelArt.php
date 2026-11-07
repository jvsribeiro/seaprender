<?php

namespace App\Support;

final class PixelArt
{
    private const PALETTE = [
        'o' => '#071A33',
        'a' => '#004C94',
        'b' => '#2E7BC4',
        'y' => '#F7941D',
        'l' => '#FBC483',
        'd' => '#C96A00',
        'w' => '#FFFFFF',
        'p' => '#FBC483',
        's' => '#FFE3C2',
    ];

    private const SPRITES = [
        'home' => [
            '.....ww.....',
            '....wwww....',
            '...wwwwww...',
            '..wwwwwwww..',
            '.wwwwwwwwww.',
            'wwwwwwwwwwww',
            '..wwwwwwww..',
            '..www..www..',
            '..www..www..',
            '..www..www..',
            '..www..www..',
            '..www..www..',
        ],
        'book' => [
            '............',
            '.wwww..wwww.',
            'w....ww....w',
            'w.ww.ww.ww.w',
            'w....ww....w',
            'w.ww.ww.ww.w',
            'w....ww....w',
            'w.ww.ww.ww.w',
            'w....ww....w',
            '.wwwwwwwwww.',
            '.....ww.....',
            '............',
        ],
        'lens' => [
            '...wwwww....',
            '..ww...ww...',
            '.ww.....ww..',
            '.w.......w..',
            '.w.......w..',
            '.w.......w..',
            '.ww.....ww..',
            '..ww...ww...',
            '...wwwwww...',
            '.......www..',
            '........www.',
            '.........www',
        ],
        'users' => [
            '...www......',
            '..wwwww.ww..',
            '..wwwww.www.',
            '..wwwww.www.',
            '...www..ww..',
            '............',
            '.wwwwwww.ww.',
            'wwwwwwwww.ww',
            'wwwwwwwww.ww',
            'wwwwwwwww.ww',
            'wwwwwwwww.ww',
            '............',
        ],
        'scroll' => [
            '.wwwwwwwwww.',
            'w..........w',
            'w.wwwwwwww.w',
            'w..........w',
            'w.wwwwwwww.w',
            'w..........w',
            'w.wwwww....w',
            'w..........w',
            'w.wwwwwwww.w',
            'w..........w',
            'w..........w',
            '.wwwwwwwwww.',
        ],
        'check' => [
            '..........ww',
            '.........www',
            '........www.',
            '.......www..',
            'ww....www...',
            'www..www....',
            '.wwwwww.....',
            '..wwww......',
            '...ww.......',
        ],
        'coin' => [
            '....oooo....',
            '..oollyyoo..',
            '.ollyyyyydo.',
            '.olyyooyydo.',
            'olyyyooyyydo',
            'olyyyooyyydo',
            'olyyyooyyydo',
            'olyyyooyyydo',
            '.olyyooyydo.',
            '.oyyyyyyddo.',
            '..ooyyddoo..',
            '....oooo....',
        ],
        'star' => [
            '......o......',
            '.....oyo.....',
            '.....oyo.....',
            '....oyyyo....',
            'ooooyyyyyoooo',
            'oyyyyyyyyyyyo',
            '.oyyyyyyyyyo.',
            '..oyyyyyyyo..',
            '..oyyyyyyyo..',
            '.oyyyoooyyyo.',
            '.oyyo...oyyo.',
            'oyoo.....ooyo',
            'oo.........oo',
        ],
        'trophy' => [
            '..oooooooo..',
            'oooyllyyyooo',
            'o.oyllyyyo.o',
            'o.oyllyyyo.o',
            'oooyllyyyooo',
            '..oyyyyyyo..',
            '...oyyyyo...',
            '....oyyo....',
            '.....oo.....',
            '....oyyo....',
            '...oyyyyo...',
            '..oooooooo..',
        ],
        'chest' => [
            '..oooooooo..',
            '.obbbbbbbbo.',
            'obaaaaaaaabo',
            'oaaaaaaaaaao',
            'oooooyyooooo',
            'oyyyyyllyyyo',
            'oaaaaayyaaao',
            'oaaaaaaaaaao',
            'oaaaaaaaaaao',
            'oooooooooooo',
        ],
        'cloud' => [
            '......wwww......',
            '...wwwwwwwwww...',
            '..wwwwwwwwwwww..',
            '.wwwwwwwwwwwwww.',
            'wwwwwwwwwwwwwwww',
            'ssssssssssssssss',
        ],
    ];

    /**
     * @param  array<string, string>  $palette
     */
    public static function rects(string $name, array $palette = []): string
    {
        $colors = array_merge(self::PALETTE, $palette);
        $output = '';

        foreach (self::SPRITES[$name] as $y => $row) {
            $x = 0;
            $width = strlen($row);

            while ($x < $width) {
                $char = $row[$x];
                $run = 1;

                while ($x + $run < $width && $row[$x + $run] === $char) {
                    $run++;
                }

                if ($char !== '.') {
                    $output .= sprintf('<rect x="%d" y="%d" width="%d" height="1" fill="%s"/>', $x, $y, $run, $colors[$char]);
                }

                $x += $run;
            }
        }

        return $output;
    }

    /**
     * @param  array<string, string>  $palette
     */
    public static function svg(string $name, string $class = '', array $palette = []): string
    {
        $rows = self::SPRITES[$name];

        return sprintf(
            '<svg class="%s" viewBox="0 0 %d %d" shape-rendering="crispEdges" aria-hidden="true" focusable="false">%s</svg>',
            e($class),
            strlen($rows[0]),
            count($rows),
            self::rects($name, $palette),
        );
    }
}
