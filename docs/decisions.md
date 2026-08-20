# Architecture Decisions

Short records of choices made during the build, with the reasoning and the
alternatives that were rejected. Newest entries at the bottom.

---

## 2026-08-16 — PostgreSQL for the entire application

Postgres 17 with pgvector, in Docker on port 5433 (5432 was occupied).

One database for both relational data and embeddings. Splitting vectors into a
dedicated store (Pinecone, Qdrant) would mean synchronising two systems and
losing the ability to join chunks against their parent documents in a single
query. At this scale pgvector is comfortably sufficient.

---

## 2026-08-16 — Status transitions live on the enum, not in state classes

`RequestStatus` is a backed enum carrying `allowedNext()` and
`canTransitionTo()`. A single action performs the transition and throws
`InvalidStatusTransition` when the move is not permitted.

Rejected: a class per state (the classic State pattern). Eight states would mean
eight classes plus an interface to express rules that fit in one match
expression. The enum version is still fully testable — every invalid transition
can be asserted — which is the property that actually matters here.

---

## 2026-08-16 — Estimated cost is derived, not a separate agent tool

`ClassifyRequest` returns `estimated_hours` alongside category and urgency.
Cost is that figure multiplied by the hourly rate of the cheapest available
vendor, computed after `FindAvailableVendors` returns.

Keeps the tool count at seven and puts the escalation threshold check at a
natural point in the pipeline. An eighth "EstimateCost" tool would need the same
vendor data `FindAvailableVendors` has already fetched.

---

## 2026-08-16 — Vendor availability derived from work orders

A vendor is available when they are active, their trade matches, and they have
no work order overlapping the target slot. Working hours are assumed to be
weekdays 09:00–17:00.

Rejected: a `vendor_availability` table. Real availability windows are a
scheduling problem well outside the scope of a triage demonstration. If per-
vendor hours are ever needed, that is an additive migration rather than a
rewrite.

---

## 2026-08-18 — Enum values stored as strings, cast in models

Migrations declare `string` columns. Validation lives in backed PHP enums cast
at the model layer.

Rejected: `$table->enum()`, which Postgres compiles to a varchar plus a CHECK
constraint — changing the permitted set then requires raw SQL in a migration.
Also rejected: lookup tables, since the agent branches on these values in code,
so adding one always requires a code change anyway. A lookup table would buy
runtime editability that cannot actually be used, at the cost of a join.

---

## 2026-08-18 — Vector column and index use Laravel's native support

`$table->vector('embedding', dimensions: 768)->nullable()->index()`, preceded by
`Schema::ensureVectorExtensionExists()`. Laravel 13 builds the HNSW index with
cosine distance from `->index()`; no raw SQL is needed.

768 is fixed by `nomic-embed-text`. Changing embedding model means a migration
and a full re-embed — vectors are not portable between models.

Nullable because ingestion runs in two phases: insert chunks, then embed them.
This makes `whereNull('embedding')` a usable query for outstanding work and
allows a failed run to be resumed.

---

## 2026-08-18 — AI SDK conversation tables kept, but not the audit source

The SDK's `agent_conversations` and `agent_conversation_messages` tables are
left in place as a low-level transcript for debugging.

`agent_decisions` remains the source of truth for the UI. The SDK tables log
infrastructure (what messages were sent to the model); `agent_decisions` logs
domain events (what the system concluded about a request, and why). Keeping a
separate table means the audit trail does not depend on a third-party schema.

---

## 2026-08-19 — Transition enforcement split across three files

`RequestStatus::canTransitionTo()` answers whether a move is permitted and
stays a pure query, so the UI can call it to decide which actions to render
without wrapping the check in a try/catch. `TransitionRequestStatus` performs
the move and throws `InvalidStatusTransition` when the answer is no.

The enum holds the rules because they depend only on the current state. If a
rule ever needs external context — "cannot close while a work order is open" —
the rules move to a service and the enum reverts to a plain vocabulary. That
is the expiry condition for this design.

`InvalidStatusTransition` extends `DomainException` rather than `Exception`:
reaching it means the caller failed to check first, which is a logic error
rather than a runtime condition.

---

## 2026-08-20 — Tests run against Postgres, not SQLite

`phpunit.xml` sets `DB_CONNECTION=pgsql` and `DB_DATABASE=maintenance_triage_test`.
Host, port, and credentials come from `.env`, so no secrets are committed.

SQLite is not an option: `document_chunks` uses pgvector, which SQLite has no
equivalent for. Testing on a different engine than the app runs on would also
hide schema-level bugs.

A separate database is required because `RefreshDatabase` truncates between
tests, which would destroy development data.

---

## 2026-08-21 — Policy documents stored as markdown fixtures

The five knowledge base documents live in `database/seeders/documents/` as
markdown, committed to the repository.

Markdown rather than PDF: the ingestion path is read-file-and-store, with no
parser in between. PDF text extraction loses table structure and breaks line
endings, adding failure modes for no demonstration value. Headings also give
natural chunk boundaries, so chunks land on clause boundaries rather than at
arbitrary character counts.

`database/seeders/` rather than `public/` or `storage/app/public`: these are
fixtures that ship with the repository, not user uploads, and there is no
reason to serve them over HTTP.

If PDF sources are ever needed, a parser sits in front of the same pipeline —
chunking, embedding, and search are unchanged.

---

## 2026-08-21 — Factory embeddings default to null

`DocumentChunkFactory` sets `embedding` to null. An `embedded()` state
generates 768 random floats.

Random vectors are structurally valid but semantically meaningless — they sit
at an arbitrary point in the space, unrelated to the chunk's text, so any
similarity search against them returns noise. Real embeddings come only from
Ollama during ingestion.

The random state exists to test the storage path — that the column accepts 768
floats and the array cast round-trips correctly — not to test retrieval
quality.
