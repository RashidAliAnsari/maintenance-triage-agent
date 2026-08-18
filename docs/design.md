# Maintenance Triage Agent

## Problem

Property managers handle every maintenance request manually: reading the tenant's
description, judging urgency, checking the lease to decide who pays, finding an
available contractor, and scheduling the job. This is slow and inconsistent.

This project demonstrates an AI agent that performs that triage automatically —
classifying the request, consulting policy documents, assigning a contractor, and
escalating to a human when the decision is not clear-cut.

Scope note: this is a demonstration system, not a commercial product.

## Actors

- **Tenant** — lives in the unit, reports the issue
- **Property Manager** — manages the property on the owner's behalf; primary user
- **Property Owner** — owns the property; approves spending above a threshold
- **Service Provider** — plumber, electrician, HVAC technician; performs the repair

## Domain Model

| Entity             | Key fields                                                             | Relationships                            |
| ------------------ | ---------------------------------------------------------------------- | ---------------------------------------- |
| Property           | name, address                                                          | has many Units                           |
| Unit               | unit_number                                                            | belongs to Property, has many Leases     |
| Tenant             | name, email, phone                                                     | has many Leases                          |
| Lease              | start_date, end_date                                                   | belongs to Unit and Tenant               |
| MaintenanceRequest | description, category, urgency, status, responsibility, estimated_cost | belongs to Unit and Tenant               |
| Vendor             | name, email, trade, hourly_rate, is_active                             | has many WorkOrders                      |
| WorkOrder          | scheduled_for, status, notes                                           | belongs to MaintenanceRequest and Vendor |
| PolicyDocument     | title, type, content                                                   | has many DocumentChunks                  |
| DocumentChunk      | content, embedding (vector)                                            | belongs to PolicyDocument                |
| AgentDecision      | request_id, tool_calls, reasoning, confidence, outcome                 | belongs to MaintenanceRequest            |

Categories: plumbing, electrical, HVAC, appliance, structural, other
Urgency: emergency, urgent, routine
Responsibility: landlord, tenant, warranty, unclear

## Request Lifecycle

1. **submitted** — tenant reports the issue
2. **triaging** — agent classifies category and urgency
3. **assessing** — agent searches policy documents to determine responsibility
4. **escalated** — handed to a human, who either assigns a vendor or closes it
5. **assigned** — vendor selected and work order created
6. **scheduled** — appointment set, tenant notified
7. **completed** — work finished
8. **closed** — request archived

## Agent Design

A single Laravel agent using the Laravel AI SDK, provider-agnostic (Ollama locally,
Claude or OpenAI in production). The agent receives the request description and unit
context, then selects tools until it either completes triage or escalates.

Tools:

| Tool                    | Input                                  | Output                                      |
| ----------------------- | -------------------------------------- | ------------------------------------------- |
| ClassifyRequest         | description                            | category, urgency, confidence               |
| SearchPolicyDocuments   | query, document_type                   | relevant policy excerpts (RAG)              |
| DetermineResponsibility | category, description, policy excerpts | landlord / tenant / warranty / unclear      |
| FindAvailableVendors    | trade, urgency                         | list of vendors with rates and availability |
| CreateWorkOrder         | request_id, vendor_id, scheduled_for   | work order record                           |
| NotifyTenant            | request_id, message                    | email sent                                  |
| EscalateToHuman         | request_id, reason                     | request marked escalated                    |

Every tool call, its arguments, the agent's reasoning, and the final outcome are
written to AgentDecision for audit.

## Knowledge Base (RAG)

Documents stored in PostgreSQL with pgvector; embeddings generated locally via Ollama
(nomic-embed-text). Documents are chunked, embedded, and retrieved by similarity search.

Document types:

- Lease agreement clauses covering maintenance responsibility
- Property management agreement (spending limits, owner approval rules)
- Appliance warranty terms
- Vendor rate sheets
- Maintenance SOP handbook

## Escalation Rules

The agent stops and escalates when any of these hold:

- Estimated cost exceeds the owner-approved threshold (default: $500)
- Responsibility is determined as "unclear"
- Urgency is classified as "emergency" (liability risk — always human-reviewed)
- Classification confidence falls below threshold
- No vendor is available within the SLA window
- Policy search returns no relevant excerpts

## Failure Modes

| Failure                                           | Handling                                                        |
| ------------------------------------------------- | --------------------------------------------------------------- |
| LLM returns malformed tool arguments              | Validate against schema; retry once; escalate on second failure |
| LLM API times out or is unavailable               | Queue job retries with backoff; request stays in current state  |
| Agent loops without reaching a decision           | Hard cap on tool-call iterations, then escalate                 |
| Policy search returns nothing relevant            | Treat responsibility as "unclear" and escalate                  |
| No vendor available for the trade or window       | Escalate to property manager                                    |
| Duplicate request submitted for same issue        | Flag for human review, do not auto-assign                       |
| Local model quality too low for reliable tool use | Provider is swappable via config                                |

## Non-Goals

This system does **not** include:

- A tenant-facing portal or mobile app
- Payment processing or invoicing
- Multi-company / multi-tenant support (single property management company only)
- Vendor-facing accounts (vendors are notified by email only)
- Real-time chat between tenant and manager
- Accounting, reporting, or analytics dashboards
- Autonomous action on emergencies (always human-reviewed)

## Tech Stack

- Laravel 13 (PHP 8.3), served via Herd
- Livewire (admin UI)
- Laravel AI SDK (agent, tools)
- PostgreSQL 17 + pgvector (Docker)
- Ollama — qwen3:8b (agent), nomic-embed-text (embeddings)
- Pest (tests)

## Future Work

- Evaluation harness comparing tool-selection accuracy across models
- MCP server exposing these tools to external AI clients
