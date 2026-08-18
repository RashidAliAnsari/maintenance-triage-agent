<?php

declare(strict_types=1);

namespace App\Enums;

enum RequestUrgency: string
{
    case Emergency = 'emergency';
    case Urgent = 'urgent';
    case Routine = 'routine';

    public function label(): string
    {
        return match ($this) {
            self::Emergency => 'Emergency',
            self::Urgent => 'Urgent',
            self::Routine => 'Routine',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
