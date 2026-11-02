# 15 - Contracts processing (backend)

Purpose
- Extract contracts from uploaded documents and persist them for reporting.

Architecture
- Queue-first: `ProcessDocumentJob` calls extraction actions then store actions.

Database changes
- `contracts` table: `team_id`, `document_id`, `external_id`, `vendor_name`, `contract_number`, `start_date`, `end_date`, `value`, `currency`, `status`, `reference`.

Actions
- `App\Actions\Documents\ExtractContractsAction` — returns deterministic sample contracts for now.
- `App\Actions\Documents\StoreContractsAction` — idempotent storage using `external_id` + `document_id`.

Job wiring
- `App\Jobs\ProcessDocumentJob` now calls the extract/store contract actions during processing.

Policies & Permissions
- `App\Policies\ContractPolicy` added.
- Permission slug: `view_contracts` added to `App\Models\Permission`.

Tests
- Feature tests and policy tests added under `tests/Feature`.

Verification checklist
- Contracts are extracted during document processing.
- Stored contracts are scoped to the current team.
- Users need `view_contracts` permission to view the contracts page and list.

Retry safety: sample contract IDs are now deterministic from document ID and contract number. A retry of the same sample extraction targets the same stored row. Real extraction must supply a stable source identity before this feature can be relied on for financial/legal records.
