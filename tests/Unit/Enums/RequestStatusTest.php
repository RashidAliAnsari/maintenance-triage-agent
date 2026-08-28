<?php

declare(strict_types=1);

use App\Enums\RequestStatus;

it('returns every case value in declaration order', function () {
    expect(RequestStatus::values())->toBe([
        'submitted',
        'triaging',
        'assessing',
        'escalated',
        'assigned',
        'scheduled',
        'completed',
        'closed',
    ]);
});

it('has a label for every case', function (RequestStatus $status) {
    expect($status->label())->not->toBeEmpty();
})->with(RequestStatus::cases());

it('allows exactly the documented transitions from each status', function (RequestStatus $from, array $expected) {
    expect($from->allowedNext())->toBe($expected);
})->with([
    'submitted' => [RequestStatus::Submitted, [RequestStatus::Triaging]],
    'triaging' => [RequestStatus::Triaging, [RequestStatus::Assessing, RequestStatus::Escalated]],
    'assessing' => [RequestStatus::Assessing, [RequestStatus::Assigned, RequestStatus::Escalated]],
    'escalated' => [RequestStatus::Escalated, [RequestStatus::Assigned, RequestStatus::Closed]],
    'assigned' => [RequestStatus::Assigned, [RequestStatus::Scheduled, RequestStatus::Escalated]],
    'scheduled' => [RequestStatus::Scheduled, [RequestStatus::Completed, RequestStatus::Escalated]],
    'completed' => [RequestStatus::Completed, [RequestStatus::Closed]],
    'closed' => [RequestStatus::Closed, []],
]);

it('permits a valid transition', function (RequestStatus $from, RequestStatus $to) {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    'submitted to triaging' => [RequestStatus::Submitted, RequestStatus::Triaging],
    'triaging to assessing' => [RequestStatus::Triaging, RequestStatus::Assessing],
    'assessing to assigned' => [RequestStatus::Assessing, RequestStatus::Assigned],
    'assigned to scheduled' => [RequestStatus::Assigned, RequestStatus::Scheduled],
    'scheduled to completed' => [RequestStatus::Scheduled, RequestStatus::Completed],
    'completed to closed' => [RequestStatus::Completed, RequestStatus::Closed],
    'escalated to assigned' => [RequestStatus::Escalated, RequestStatus::Assigned],
    'escalated to closed' => [RequestStatus::Escalated, RequestStatus::Closed],
]);

it('refuses a transition that skips a stage', function (RequestStatus $from, RequestStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'submitted straight to assessing' => [RequestStatus::Submitted, RequestStatus::Assessing],
    'submitted straight to assigned' => [RequestStatus::Submitted, RequestStatus::Assigned],
    'submitted straight to closed' => [RequestStatus::Submitted, RequestStatus::Closed],
    'triaging straight to assigned' => [RequestStatus::Triaging, RequestStatus::Assigned],
    'assessing straight to scheduled' => [RequestStatus::Assessing, RequestStatus::Scheduled],
    'assessing straight to completed' => [RequestStatus::Assessing, RequestStatus::Completed],
    'assigned straight to completed' => [RequestStatus::Assigned, RequestStatus::Completed],
    'scheduled straight to closed' => [RequestStatus::Scheduled, RequestStatus::Closed],
]);

it('refuses a transition that moves backwards', function (RequestStatus $from, RequestStatus $to) {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'triaging back to submitted' => [RequestStatus::Triaging, RequestStatus::Submitted],
    'assessing back to triaging' => [RequestStatus::Assessing, RequestStatus::Triaging],
    'assigned back to assessing' => [RequestStatus::Assigned, RequestStatus::Assessing],
    'scheduled back to assigned' => [RequestStatus::Scheduled, RequestStatus::Assigned],
    'completed back to scheduled' => [RequestStatus::Completed, RequestStatus::Scheduled],
    'escalated back to triaging' => [RequestStatus::Escalated, RequestStatus::Triaging],
]);

it('refuses every transition out of closed', function (RequestStatus $to) {
    expect(RequestStatus::Closed->canTransitionTo($to))->toBeFalse();
})->with(RequestStatus::cases());

it('refuses a transition to itself', function (RequestStatus $status) {
    expect($status->canTransitionTo($status))->toBeFalse();
})->with(RequestStatus::cases());

it('treats only closed as terminal', function (RequestStatus $status, bool $expected) {
    expect($status->isTerminal())->toBe($expected);
})->with([
    'submitted' => [RequestStatus::Submitted, false],
    'triaging' => [RequestStatus::Triaging, false],
    'assessing' => [RequestStatus::Assessing, false],
    'escalated' => [RequestStatus::Escalated, false],
    'assigned' => [RequestStatus::Assigned, false],
    'scheduled' => [RequestStatus::Scheduled, false],
    'completed' => [RequestStatus::Completed, false],
    'closed' => [RequestStatus::Closed, true],
]);
