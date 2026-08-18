<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which party bears the cost of a repair.
 */
enum Responsibility: string
{
    case Landlord = 'landlord';
    case Tenant = 'tenant';
    case Warranty = 'warranty';
    case Unclear = 'unclear';

    public function label(): string
    {
        return match ($this) {
            self::Landlord => 'Landlord',
            self::Tenant => 'Tenant',
            self::Warranty => 'Warranty',
            self::Unclear => 'Unclear',
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
