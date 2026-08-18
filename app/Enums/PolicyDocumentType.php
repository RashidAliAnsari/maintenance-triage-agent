<?php

declare(strict_types=1);

namespace App\Enums;

enum PolicyDocumentType: string
{
    case Lease = 'lease';
    case ManagementAgreement = 'management_agreement';
    case Warranty = 'warranty';
    case RateSheet = 'rate_sheet';
    case Sop = 'sop';

    public function label(): string
    {
        return match ($this) {
            self::Lease => 'Lease Agreement',
            self::ManagementAgreement => 'Management Agreement',
            self::Warranty => 'Appliance Warranty',
            self::RateSheet => 'Vendor Rate Sheet',
            self::Sop => 'Maintenance SOP',
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