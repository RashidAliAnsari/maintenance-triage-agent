<?php

declare(strict_types=1);

use App\Actions\TransitionRequestStatus;
use App\Enums\RequestStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\MaintenanceRequest;

it('moves a request to a permitted status', function () {
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Submitted]);

    $returned = app(TransitionRequestStatus::class)->handle($request, RequestStatus::Triaging);

    expect($returned->status)->toBe(RequestStatus::Triaging);
});

it('persists the new status to the database', function () {
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Submitted]);

    app(TransitionRequestStatus::class)->handle($request, RequestStatus::Triaging);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $request->id,
        'status' => RequestStatus::Triaging->value,
    ]);
});

it('returns the same model instance as it was given', function () {
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Submitted]);

    $returned = app(TransitionRequestStatus::class)->handle($request, RequestStatus::Triaging);

    expect($returned->is($request))->toBeTrue();
});

it('throws when the transition is not permitted', function () {
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Submitted]);

    app(TransitionRequestStatus::class)->handle($request, RequestStatus::Assigned);
})->throws(InvalidStatusTransition::class);

it('leaves the database unchanged when it throws', function () {
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Submitted]);

    expect(fn () => app(TransitionRequestStatus::class)->handle($request, RequestStatus::Assigned))
        ->toThrow(InvalidStatusTransition::class);

    $this->assertDatabaseHas('maintenance_requests', [
        'id' => $request->id,
        'status' => RequestStatus::Submitted->value,
    ]);
});

it('walks the full happy path from submitted to closed', function () {
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Submitted]);
    $action = app(TransitionRequestStatus::class);

    foreach ([
        RequestStatus::Triaging,
        RequestStatus::Assessing,
        RequestStatus::Assigned,
        RequestStatus::Scheduled,
        RequestStatus::Completed,
        RequestStatus::Closed,
    ] as $next) {
        $action->handle($request, $next);
    }

    expect($request->fresh()->status)->toBe(RequestStatus::Closed);
});

it('refuses to move out of a terminal status', function () {
    $request = MaintenanceRequest::factory()->create(['status' => RequestStatus::Closed]);

    app(TransitionRequestStatus::class)->handle($request, RequestStatus::Assigned);
})->throws(InvalidStatusTransition::class);

it('rejects every illegal transition', function (RequestStatus $from, RequestStatus $to) {
    $request = MaintenanceRequest::factory()->create(['status' => $from]);

    app(TransitionRequestStatus::class)->handle($request, $to);
})->with([
    'submitted to completed' => [RequestStatus::Submitted, RequestStatus::Completed],
    'triaging to assigned' => [RequestStatus::Triaging, RequestStatus::Assigned],
    'assessing to scheduled' => [RequestStatus::Assessing, RequestStatus::Scheduled],
    'assigned to completed' => [RequestStatus::Assigned, RequestStatus::Completed],
    'completed back to assigned' => [RequestStatus::Completed, RequestStatus::Assigned],
])->throws(InvalidStatusTransition::class);
