<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\RequestStatus;
use DomainException;

final class InvalidStatusTransition extends DomainException
{
    public static function between(RequestStatus $from, RequestStatus $to): self
    {
        return new self(sprintf(
            'Cannot transition a maintenance request from %s to %s',
            $from->value,
            $to->value,
        ));
    }
}
