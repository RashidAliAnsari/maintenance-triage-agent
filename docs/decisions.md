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

---

## 2026-08-23 — Factory defaults are the earliest valid lifecycle state

`definition()` returns the state where nothing has happened yet.
`MaintenanceRequest` defaults to `submitted` with category, urgency,
responsibility, and both estimates null. `WorkOrder` defaults to `pending` with
no `scheduled_for`, matching the gap between vendor selection and booking.

Rejected: randomising status across the whole lifecycle so seeded data looks
varied. Later states carry obligations — an `assigned` request needs a
responsibility, a `completed` work order needs to have been scheduled — so a
random `completed` produces rows that are structurally valid but domain
nonsense. A test then passes for the wrong reason, or fails in a way that takes
an hour to trace back to seed data.

Variety comes from named states instead, which also makes the intent of a test
readable at the call site.

States compose rather than repeat: `assessed()` calls `classified()`,
`escalated()` and `assigned()` call `assessed()`, and the work order states
build on `scheduled()`. Each sets only what its own pipeline step produces, so a
change to what "classified" means lands in one place.

---

## 2026-08-23 — Seeded values are derived, not independently random

`estimated_cost` is computed as `estimated_hours` multiplied by a vendor rate.
`category` is looked up from the description rather than picked at random.

Same rule in both cases: if a value is a function of another value in
production, the factory computes it the same way. A row with 3 hours and an
unrelated $840 cost could never come out of the real pipeline, so a test
asserting on the cost calculation would be checking against fiction.

The description-to-category map matters most. The agent reads that text and
decides what it is, so Faker lorem would give a `ClassifyRequest` test no
correct answer to assert against — and a wrong classification could not be
distinguished from meaningless input.

Hours are capped at 0.5–4 so the derived cost stays under the $500 approval
threshold. Unbounded, roughly one in every few `assigned()` calls would land
above it and produce a request that should have escalated — a test that passes
ninety times and fails once.

Urgency is left random. Category is objectively determined by the description;
urgency depends on context the description does not always carry, so a test
asserting on it should pin it explicitly.

---

## 2026-08-23 — Work order slots obey the derived-availability rule

`WorkOrderFactory` schedules into weekday 09:00–17:00 slots on the half hour,
correcting weekend dates onto the nearest weekday in whichever direction the
slot was already heading.

Vendor availability is derived from work orders rather than stored, so
`FindAvailableVendors` computes against these rows. Slots scattered across
Sunday nights would mean the tool reasoning about appointments that could not
exist. Seed data has to obey the same constraints the code will.

Latest start is 16:30 so the appointment finishes inside working hours, and
times land on the hour or half hour because real booking systems do.

`CarbonImmutable` rather than `Carbon`: the corrections chain, and an immutable
instance cannot be modified in place by a call whose return value is discarded.

---

## 2026-09-14 — Policy documents chunked on H2 headings only

`MarkdownChunker` splits each document at `## ` headings. H3 subheadings
stay inside their parent chunk, the H1 title and metadata block before the
first H2 are discarded, and `---` separators are stripped. The heading line
is kept as the first line of each chunk's text.

Subclauses cross-reference each other within a section (Clause 15 points
back to 12.5 and 13.1), so splitting below H2 would separate text the agent
needs together. Only the SOP uses real H3 headings; the other documents mark
subclauses with bold text, so splitting on H3 would give one document finer
chunks than the rest.

Keeping the heading in the chunk text means no `heading` column on
`document_chunks` and no migration. The retrieval classes live under
`App\Ai\Rag`, next to the SDK's own `App\Ai\Agents` and `App\Ai\Tools`.

See 2026-10-30 on what this granularity costs in retrieval precision.

---

## 2026-10-30 — Fixture filenames match PolicyDocumentType values

The five fixtures are named after the enum's backing values (`lease.md`,
`warranty.md`, `rate_sheet.md`, and so on), so `documents:ingest` maps each
file to its type with `PolicyDocumentType::from()`. An unknown file in the
folder throws instead of being stored under a guessed type.

Rejected: changing the enum values to match the old filenames. The values
are what is stored in `policy_documents.type`, and renaming five fixtures
touches nothing else.

---

## 2026-10-30 — Ingestion rebuilds a document's chunks on every run

`documents:ingest` upserts one `PolicyDocument` per type with
`updateOrCreate`, then deletes that document's chunks and inserts them
again. Running it repeatedly never duplicates rows, and editing a fixture
never leaves stale chunks behind.

The consequence is that every run re-embeds all chunks. The
`whereNull('embedding')` resume path from 2026-08-18 now only matters when
the embedding call fails after chunks are written. At 26 chunks a full
re-embed takes seconds. If the corpus grows, compare a content hash before
rebuilding a document.

---

## 2026-10-30 — Embedder owns the model, dimensions, and task prefixes

`App\Ai\Rag\Embedder` is the only class that calls the embeddings API.
Ingestion and search both go through it, so a query can never be embedded
with a different model than the stored chunks.

The model and dimensions are constants, not config. Changing either means a
migration and a full re-embed, so a setting would suggest a flexibility that
does not exist.

nomic-embed-text requires a task prefix on every input: `search_document:`
for stored text and `search_query:` for questions. Without them, scores for
a test query bunched within 0.03 of each other, and an unrelated query could
not be told apart from a real one. `embedDocuments()` and `embedQuery()` add
the prefixes; the underlying `embed()` is private, so no caller can skip
them. Prefixes are sent to the model only; stored chunk text stays clean.

---

## 2026-10-30 — Similarity threshold 0.45, top three results

`PolicySearch` returns at most three chunks with cosine similarity of at
least 0.45. Measured with prefixes: real policy matches scored 0.54 and
above, and an unrelated question ("best pizza in Lahore") scored 0.385 at
most. 0.45 sits in that gap, so an irrelevant question returns nothing,
which is what triggers the "no relevant policy" escalation rule.

Known limitation: H2 chunks are coarse. For "who pays for a toilet blocked
by wipes?", Clause 13 ranked third, because its vector averages six
unrelated subclauses and blockages are one of them. It is still within the
top three the agent receives.

The fix is to split at subclauses and repeat the parent heading on each
piece. Not done yet: the documents format subclauses inconsistently
(numbered in the lease, appliance names in the warranty schedule, a table in
the rate sheet), so it needs a more involved chunker.

---

## 2026-10-30 — Retrieval tests use real embeddings and skip without Ollama

Ingestion tests fake the embeddings API. Retrieval tests cannot: fake
vectors are random, so they cannot show whether a question finds the right
clause.

`PolicySearchTest` runs ingestion against the real model and skips itself
when nothing answers on port 11434. CI has no Ollama, so CI does not guard
retrieval quality. Run these tests locally before changing the chunker, the
Embedder, or the threshold.
