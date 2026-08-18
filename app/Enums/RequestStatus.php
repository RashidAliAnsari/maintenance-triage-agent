<?php

declare(strict_types=1);

namespace App\Enums;

enum RequestStatus: string
{
    case Submitted = 'submitted';
    case Triaging = 'triaging';
    case Assessing = 'assessing';
    case Escalated = 'escalated';
    case Assigned = 'assigned';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Triaging => 'Triaging',
            self::Assessing => 'Assessing Responsibility',
            self::Escalated => 'Escalated to Human',
            self::Assigned => 'Vendor Assigned',
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Closed => 'Closed',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Submitted => [self::Triaging],
            self::Triaging => [self::Assessing, self::Escalated],
            self::Assessing => [self::Assigned, self::Escalated],
            self::Escalated => [self::Assigned, self::Closed],
            self::Assigned => [self::Scheduled, self::Escalated],
            self::Scheduled => [self::Completed, self::Escalated],
            self::Completed => [self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedNext(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedNext() === [];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}