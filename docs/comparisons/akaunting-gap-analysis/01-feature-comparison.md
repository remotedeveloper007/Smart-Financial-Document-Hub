# Feature Comparison and Product Verdict

## Product Fit

| Dimension | Smart Financial Document Hub | Akaunting | Assessment |
|---|---|---|---|
| Primary job | Ingest financial documents, extract structured records, search/export, integrate with accounting systems | Run small-business accounting workflows and maintain business books | Adjacent products with different centers of gravity |
| Tenant/company model | Team-based `team_id`, global Eloquent scope and job context | Company-scoped accounting data and users; visible in company-centric schema and docs | Both support organization-level separation; Hub has its own tenant foundation, but must keep expanding cross-boundary tests |
| Financial truth | Document-derived rows; current extractors for transactions/invoices/receipts/contracts return fixed sample data | Accounting records such as invoices, bills, accounts, transactions, and journal/accounting workflows | Akaunting is substantially more mature for actual bookkeeping |
| Core ledger | No chart-of-accounts, journal-entry, general-ledger, trial-balance, or double-entry implementation found | Public docs describe double-entry, chart of accounts, manual journals, and general ledger | Critical gap if Hub intends to become an accounting system; avoid scope creep if it remains a document-intelligence layer |
| Sales and receivables | Invoice extraction/list foundation; no full customer invoice authoring, delivery, payment lifecycle, receivable ledger, or client portal found | Invoices, estimates, customers, online payment documentation and recurring invoices are present in repository/product docs | Akaunting leads substantially |
| Purchases and payables | Receipt/invoice extraction lists; no complete bill approval/payment/vendor balance workflow found | Bills, vendors, bill payments, and recurring bills documented and present in core accounting structure | Akaunting leads substantially |
| Banking | Transaction sample/list/search/CSV export foundation | Bank/cash/credit-card accounts, transaction lifecycle, transfers, feeds and reconciliation routes/features | Akaunting leads substantially |
| Financial reports | System health and search pages; no full ledger-backed P&L, balance sheet, tax or cash-flow reports found | Income/expense, income-vs-expense, profit/loss, tax and other reports documented | Akaunting leads substantially |
| Document automation | Queue and document-processing job structure, but upload currently stores on public disk and scan is not proven in the processing path; extraction is sample output | Document/accounting documents are supported, but document intelligence is not its main differentiation | Hub's strategic opportunity, not yet a verified advantage |
| Integrations | Settings, OAuth foundations, chunked CSV, queue import/export. Tally is a generic REST adapter and live provider accuracy is unverified | REST API and extension/app ecosystem described in upstream materials; bank feeds and integrations vary by app/plan | Akaunting ecosystem is more mature; Hub's per-team credentials and document-to-accounting pipeline are a potential differentiator |
| Identity and authorization | Laravel session authentication, email verification, team roles, policies and admin gates | Users, roles, permissions, accountant/customer role models and workflows | Both have foundations; Akaunting has broader product-specific role workflows |
| Localization and company settings | No multi-currency/company-localization system found beyond currency fields on extracted records | Localization, company settings, currency and tax configuration documented | Akaunting leads |
| Extensibility | Modular Laravel structure, no Hub-specific module marketplace observed | Modular app/extension ecosystem is a core public product capability | Akaunting leads |
| Monitoring/faults | Queue status, health summary, fault/failed-job admin pages exist; health categorization and fault metadata remain partial | Production observability details differ by deployment/edition; not treated as a primary accounting feature in this comparison | Hub has a dedicated operational UI foundation, but completeness and alerting need work |
| Delivery maturity | Source has unit/feature tests and docs; verified current gates pass, but real extraction/provider and staging work remain | Public repository provides a broad, long-running accounting product and CI workflow; this review does not certify its current production security | Akaunting is more complete as a working accounting product |

## What Akaunting Appears to Have That the Hub Does Not

Based on the upstream repository and public docs inspected for this review:

- Accounts / chart-of-accounts concepts, manual journals, double-entry workflows, and general ledger.
- Actual customer and vendor records with balances and business relationships.
- Sales documents: estimates/quotes, invoice lifecycle, delivery, recurring invoices, payment recording, and client portal workflows.
- Purchases: bills, recurring bills, vendors, bill payment and payable tracking.
- Banking operations: cash/bank/credit-card accounts, incoming/outgoing transactions, transfers, bank feeds, and reconciliation workflows.
- Tax-rate configuration and tax-aware documents/reports.
- Product/service items and inventory capabilities; some advanced functionality is offered as an app/module.
- Accounting and management reports, dashboard widgets, cash flow, receivables/payables, and income/expense summaries.
- Broader import/export coverage, including multi-sheet business data imports and exports.
- Company configuration, localization, currency settings, and a wider permission matrix.
- Public REST API and an app/module ecosystem, subject to the specific edition and installed apps.
- Additional public help-documentation modules such as CRM, project management, inventory, expense claims, and payroll; availability may depend on paid/add-on modules.

Do not copy the whole list mechanically. Each capability adds financial domain rules, schema, testing, support, compliance and product scope.

## Hub-Specific Advantages to Preserve

- Document-to-structured-data pipeline is central rather than an afterthought.
- Team-scoped integrations store encrypted credentials per integration.
- Chunked resumable CSV upload and mapping UI provide a base for large statement imports.
- Queue, failed-job, fault and health views give operational affordances.
- The product can differentiate on traceable source-document provenance, extraction confidence, review/correction, and exporting clean data to a customer's existing ledger.

These are foundations, not yet proven competitive advantages: sample extractors must be replaced, file handling must be private and scanned before processing, and end-to-end accuracy must be measured against representative documents.

## Which Is More Advanced?

**Akaunting is more advanced and complete overall as accounting software.** It has a much larger set of implemented accounting entities and end-user workflows: invoices, bills, accounts, contacts, transactions, transfers, reconciliations, taxes, reports, imports/exports, and ecosystem features. The Hub does not currently implement double-entry bookkeeping, account balances, reconciliation, or the associated reporting rules.

**The Hub should not try to beat Akaunting by cloning it.** Its strongest product direction is an upstream document automation and data-quality layer that can feed accounting products. Later, if customer evidence demands it, add a narrow ledger/reporting capability with a formally designed accounting model.

## Evidence-Based Conclusion

Akaunting is the more advanced accounting product, while the Hub is a partial document processing platform with integration foundations. The Hub has demonstrated code/test/build foundations but not proven production financial extraction or live accounting accuracy. Prioritize secure ingestion, deterministic extraction, review/correction, provenance, tenant-safe exports, and verified provider contracts before adding broad accounting modules.
