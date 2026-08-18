<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\RequestStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\MaintenanceRequest;

class TransitionRequestStatus
{
    public function handle(MaintenanceRequest $request, RequestStatus $to): MaintenanceRequest
    {
        if (! $request->status->canTransitionTo($to)) {
            throw InvalidStatusTransition::between($request->status, $to);
        }

        $request->status = $to;
        $request->save();

        return $request;
    }
}
