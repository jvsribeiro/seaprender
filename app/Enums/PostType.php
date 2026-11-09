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
    case Reflection = 'reflection';
    case Poll = 'poll';
    case Draw = 'draw';
    case Groups = 'groups';
    case LiveInvitation = 'live_invitation';

    public function label(): string
    {
        return match ($this) {
            self::Announcement => 'Aviso',
            self::Activity => 'Atividade',
            self::Exercise => 'Exercício',
            self::Slide => 'Slide',
            self::Link => 'Link',
            self::Quiz => 'Quiz',
            self::Reflection => 'Reflexão da aula',
            self::Poll => 'Votação',
            self::Draw => 'Sorteio',
            self::Groups => 'Grupos',
            self::LiveInvitation => 'Partida ao vivo',
        };
    }

    public function sprite(): string
    {
        return match ($this) {
            self::Announcement, self::Quiz, self::Draw => 'star',
            self::Activity, self::LiveInvitation => 'trophy',
            self::Groups => 'users',
            self::Exercise, self::Link, self::Poll => 'coin',
            self::Slide, self::Reflection => 'chest',
        };
    }

    public function acceptsResponses(): bool
    {
        return $this === self::Reflection || $this === self::Poll;
    }

    /**
     * Post types available in the general mural composer.
     *
     * @return list<self>
     */
    public static function writable(): array
    {
        return [self::Announcement, self::Activity, self::Exercise, self::Slide, self::Link];
    }
}
