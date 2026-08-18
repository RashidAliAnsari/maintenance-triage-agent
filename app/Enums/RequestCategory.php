<?php

declare(strict_types=1);

namespace App\Enums;

enum RequestCategory: string
{
    case Plumbing = 'plumbing';
    case Electrical = 'electrical';
    case Hvac = 'hvac';
    case Appliance = 'appliance';
    case Structural = 'structural';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Plumbing => 'Plumbing',
            self::Electrical => 'Electrical',
            self::Hvac => 'HVAC',
            self::Appliance => 'Appliance',
            self::Structural => 'Structural',
            self::Other => 'Other',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
       return array_column(self::cases(), 'value');
    }


}
