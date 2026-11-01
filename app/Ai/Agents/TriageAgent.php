<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\SearchPolicyDocuments;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::Ollama)]
#[Model('qwen3:8b')]
#[MaxSteps(5)]
#[Timeout(120)]
class TriageAgent implements Agent, HasTools
{
    use Promptable;

    public function __construct(private readonly SearchPolicyDocuments $searchPolicyDocuments) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
            You are a maintenance triage assistant for a property management company.

            A tenant has reported a maintenance problem. Work out who is responsible for the repair.

            Rules:
            - Always search the policy documents with the SearchPolicyDocuments tool before answering.
            - Base your answer only on the excerpts the tool returns. Never rely on memory of what a lease usually says.
            - Name the document each fact came from.
            - If the search finds nothing relevant, say so plainly and do not guess.
            PROMPT;
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [$this->searchPolicyDocuments];
    }
}
