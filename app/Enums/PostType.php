<?php

namespace App\Enums;

enum PostType: string
{
    case Announcement = 'announcement';
    case Activity = 'activity';
    case Exercise = 'exercise';
    case Slide = 'slide';
    case Link = 'link';
    case Quiz = 'quiz';

    public function label(): string
    {
        return match ($this) {
            self::Announcement => 'Aviso',
            self::Activity => 'Atividade',
            self::Exercise => 'Exercício',
            self::Slide => 'Slide',
            self::Link => 'Link',
            self::Quiz => 'Quiz',
        };
    }

    public function sprite(): string
    {
        return match ($this) {
            self::Announcement, self::Quiz => 'star',
            self::Activity => 'trophy',
            self::Exercise, self::Link => 'coin',
            self::Slide => 'chest',
        };
    }

    /**
     * The types a teacher writes by hand. Quiz posts come from publishing a quiz.
     *
     * @return list<self>
     */
    public static function writable(): array
    {
        return [self::Announcement, self::Activity, self::Exercise, self::Slide, self::Link];
    }
}
