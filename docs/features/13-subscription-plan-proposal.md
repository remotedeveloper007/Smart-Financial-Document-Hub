# Subscription Plan Proposal

## Status and decision needed

**Status: proposal only. No prices, limits, or entitlements in this document have been applied to the application.**

This proposal is intended to help the product owner choose the first commercial tiers before implementation. The prices and quotas below are starting assumptions, not market research or a profitability guarantee. Confirm the target launch market, payment currency, processor fees, document costs, and AI provider costs before publishing them.

## Recommendation at a glance

- Sell access per **team/workspace**, not per seat, for the first release. Set a small included member limit and introduce seat-based pricing only if customer research supports it.
- Offer a seven-day free trial of the Free tier. Do not require a card. At expiry, stop new uploads, extraction, exports, integrations, and other paid operations immediately; keep existing records read-only for a short grace period so users can upgrade or export according to the final retention policy.
- Keep Starter inexpensive and predictable, without AI or external accounting integrations.
- Make Pro the first tier with accounting integrations and AI. Put a hard, monthly AI cost allowance on each team; do not provide unlimited AI or automatic overages.
- Keep monthly and annual paid billing. A 2-month annual discount is a simple initial offer.

## Proposed launch tiers

All amounts are illustrative USD per team/workspace. Taxes and processor fees are not included. Replace these numbers after validating willingness to pay and unit economics in the launch market.

| Tier | Illustrative price | Suggested initial limits | Included product access |
|---|---:|---|---|
| **Free trial** | $0 for 7 days | 3 members; 25 uploaded documents during trial; 500 MB stored files | Core workspace, upload, basic extraction, and in-app results preview. No AI, integrations, or export/download. |
| **Starter** | $19/month; $190/year | 3 members; 100 new documents/month; 5 GB storage | Core upload, extraction, transaction review, search, and basic collaboration. No AI or accounting integrations. |
| **Pro** | $99/month; $990/year | 10 members; 1,000 new documents/month; 50 GB storage | Starter features, team permissions, exports/downloads, accounting integrations (Zoho, Tally, QuickBooks, Xero as each integration is released), priority support, and a capped AI allowance. |

These are deliberately conservative example quotas, not established product capabilities. Validate average file size, extraction accuracy/cost, customer team size, integration support costs, and storage/egress costs before committing to them. Do not describe an integration as included until it is implemented, supported, and tested.

### Why per team for the first version

Per-workspace pricing is easier for a small business to understand and avoids penalizing collaboration as the team grows. Included member caps create a clear upgrade reason without introducing seat proration, seat-change race conditions, and more complicated billing. Revisit this after observing team sizes and support costs.

### Free expiry and access policy

Recommendation: make Free a **time-limited trial**, not a perpetual free tier. Use seven days by default; test 15 days only if onboarding requires more time. Do not stack trial days or silently reset trials by creating another workspace.

At the exact end of the trial period:

1. Revoke write and processing entitlements immediately, including uploads, extraction jobs, exports/downloads, integrations, and AI.
2. Reject new work at both the HTTP authorization and queued-job execution boundaries. A queued job must re-check tenant entitlement before doing billable work.
3. Keep the workspace and existing data read-only for a proposed 30-day upgrade grace period. Clearly warn users before expiry and during grace.
4. At grace end, follow a separately approved retention/deletion policy. Do not silently delete financial records. The user must be told how to export or request deletion where applicable.
5. On successful paid checkout/webhook, restore entitlements based on the verified subscription period. A checkout redirect alone must never unlock a paid tier.

If a perpetual free plan is commercially required instead, decide its ongoing data-retention and support cost separately; a perpetual account is not equivalent to a seven-day trial.

## Usage limits and counting rules to settle before implementation

- **Documents/month**: count one successfully accepted source document per team, not each retry, page, or extracted transaction. Decide whether failed/corrupt uploads consume a quota; recommendation: rejected uploads do not, while accepted uploads do.
- **Storage**: count actual tenant-owned bytes, including retained originals and generated files. Deleting a document should release its bytes after deletion succeeds.
- **Members**: count active team memberships, excluding pending invitations. Decide whether the owner counts; recommendation: yes.
- **Exports/downloads**: restrict by entitlement, but retain any legally required access to users' own financial data. Confirm privacy, accounting, and jurisdictional obligations before blocking access.
- **Over-limit behavior**: block the action before creating a job or provider charge, return a clear explanation, and offer upgrade or next-period reset. Do not silently overcharge.
- Display current-period usage, limit, reset date, and what happens at the limit in the billing UI.

## AI allowance policy

AI is a variable-cost capability, so do not promise unlimited AI and do not equate a fixed number of tokens with a fixed monetary cost across models.

### Recommended initial policy

- AI is available on Pro only.
- Give each Pro team a configurable monthly AI **cost budget**. Suggested initial ceiling: the lower of **10% of collected Pro subscription revenue** (net of tax, discounts, and refunds) or a product-owner-approved absolute amount per team per month.
- The illustrative $99 Pro price therefore suggests a maximum AI provider-cost budget of about $9.90 per paid team-month before adjustments. This is a ceiling for testing, not a guaranteed included amount or customer-facing credit value.
- Track actual provider/model input, output, cached-token, and other billable units where available. Convert them to cost using a versioned rate card. Record estimated and reconciled cost separately if provider billing data arrives later.
- Enforce a hard budget before dispatching each AI job. Reserve estimated cost atomically, settle to actual cost on completion, release the reservation on failure, and prevent concurrent jobs from overspending the tenant allowance.
- At 80% and 100% of allowance, notify team billing managers. At 100%, stop included AI jobs until the next paid billing period, a plan change, or a separately purchased AI credit pack is available. Do not silently charge an overage.
- Never run AI on Free or Starter. AI jobs must carry tenant, subscription-period, and usage-reservation identifiers and re-check entitlement at execution time.

### Optional AI credit packs after the included allowance

Let Pro teams buy additional AI capacity after using the included monthly allowance. For the first version, use **prepaid credit packs** rather than automatic overages:

- Offer credit packs only when the included allowance is exhausted. Show the pack price, the units/cost budget it represents, and a reasonable usage estimate before the billing manager confirms purchase. Estimates must be labeled as estimates because different models and tasks have different costs.
- Require an owner or authorized billing manager to purchase. Create a purchase/payment attempt with an idempotency key and grant credits only after a verified, deduplicated provider webhook confirms successful payment.
- Keep a tenant-scoped, append-only ledger for purchased credits, reservations, actual AI costs, releases, refunds, and adjustments. Do not update a single balance without preserving the transactions that explain it.
- Reserve credits atomically before queue dispatch; settle to actual measured cost after completion; release unused reservations on failure. Enforce the budget again inside the job so delayed or replayed jobs cannot bypass current entitlements.
- Set a hard spend cap per pack and per team-month, and never permit a negative balance. If the estimate exceeds available credits, ask the user to buy another pack or choose a lower-cost option; do not partly run a task without clearly defining that behavior.
- Recommended initial expiry: purchased credits remain usable while the team has an active paid subscription, then remain available through the approved read-only grace period. At grace end, handle unused credits according to clearly disclosed purchase terms and applicable consumer law; do not silently confiscate or auto-refund them.
- Refund unused purchased credits through the original payment provider where supported. For partially used packs, refund only the unused amount when the provider and applicable rules allow it; reverse corresponding ledger entries and retain the audit history.
- Do not enable automatic top-ups or uncapped pay-as-you-go in the first release. If introduced later, require explicit opt-in, a user-selected per-month spending cap, clear notifications, and an easy way to turn it off.

Set pack denominations and prices only after measuring AI provider rates, representative workflow costs, payment fees, failure/retry behavior, and target margin. Credit value should be denominated in a currency-linked cost budget (or an equivalently auditable unit), not a vague fixed number of prompts or documents.

### AI pricing information required before choosing a customer-facing quota

For each enabled model and workflow, record:

`cost per job = input units × input rate + output units × output rate + other billable charges`

Measure representative documents for each workflow (for example extraction assistance, categorization, and question answering), including retries and failed calls. Then calculate:

`safe monthly AI budget = min(10% × collected subscription revenue, approved absolute cap)`

and estimate the included job count from observed per-job cost, while preserving a hard monetary ceiling. Re-run the calculation whenever a model, provider rate, prompt, or workflow changes. Do not publish a fixed AI request count until this measurement exists.

## Annual billing recommendation

Offer annual billing at ten monthly payments' worth (two months effectively free): Starter $190/year and Pro $990/year for the illustrative prices above. Annual subscriptions should have one annual entitlement period and one documented refund/cancellation policy. Do not prorate or promise refunds until provider behavior and local consumer law have been reviewed.

Free trial is not an annual interval. The trial is a separate time-limited entitlement; a paid subscription starts only after verified provider confirmation.

## Existing implementation fit and gaps

- The current `plans` catalog supports a monthly `price`, optional `yearly_price`, and JSON `features`; it does not itself enforce the proposed quotas.
- Current billing supports monthly/yearly checkout and records payment attempts and subscription state. The subscription model does not yet define the trial, grace-period, or usage-reset policy described here.
- This review did not find application-side enforcement for document, storage, member, or AI quotas. Treat all limits above as proposals until a dedicated entitlement/usage feature is implemented and tested.
- Plans and values must remain server-controlled. A client-side pricing card or feature flag is not an authorization check.
- Existing plan records should not be renamed or repriced automatically. Map the approved tiers deliberately and preserve existing customer entitlements during any rollout.

## Decisions required before implementation

Please approve or change these points:

1. Billing unit: per team/workspace (recommended).
2. Free access: seven-day trial (recommended); confirm the 30-day read-only grace period.
3. Launch prices and market/currency: confirm or replace illustrative $19/$99 monthly prices and 2-month annual discount.
4. Limits: confirm member, document, and storage quotas after measuring current product usage and costs.
5. Export/download restrictions: confirm they can be restricted on Free/Starter after legal/data-portability review.
6. AI: confirm Pro-only, hard monthly cost ceiling, 10% starting budget ratio, prepaid credit packs after included usage, and no automatic top-ups at launch; provide the AI providers/models and actual rate information before setting customer-facing usage counts or pack prices.
7. Integrations: confirm which connectors are production-ready for the launch; list only those in the paid-plan comparison.
8. Grace-period data retention, cancellation, refund, tax, and upgrade/downgrade rules.

## Out of scope until approval

Do not seed or change plan prices, enforce quotas, modify subscription behavior, or build the public pricing/landing page from this proposal alone. After the decisions above are approved, implement the entitlement and usage rules first, test tenant isolation and period boundaries, then present the approved plans on the public pricing page.
