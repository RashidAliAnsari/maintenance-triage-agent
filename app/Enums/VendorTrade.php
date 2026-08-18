<?php

declare(strict_types=1);

namespace App\Enums;

enum VendorTrade: string
{
    case Plumber = 'plumber';
    case Electrician = 'electrician';
    case Hvac = 'hvac';
    case Applicance = 'appliance';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::Plumber => 'Plumber',
            self::Electrician => 'Electrician',
            self::Hvac => 'HVAC',
            self::Applicance => 'Appliance',
            self::General => 'General Contractor',
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
