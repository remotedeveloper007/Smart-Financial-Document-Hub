# Integration Import UI — CSV Mapping

Purpose
- Provide a user-friendly CSV import flow for accounting integrations (Tally).
- Allow users to map CSV columns to transaction fields before importing.

Business reason
- Many customers export transactions from other systems as CSV.
- Mapping prevents manual data cleaning and reduces errors during import.

Files added
- Frontend: `resources/js/Pages/Integrations/ImportCsvMapper.tsx`
- Frontend: integrated into `resources/js/Pages/Integrations/Index.tsx`

Frontend: `ImportCsvMapper.tsx` (line-by-line summary)
- Imports and props: component accepts `integrationId` and optional `onImported` callback.
- `TARGET_FIELDS`: list of target fields users can map CSV columns to.
- `parseCSV()`: quick CSV parser splitting on newlines and commas. Simple but sufficient for small, well-formed CSVs.
- State variables: `open`, `csvText`, `headers`, `rows`, `mapping`, `preview`, `error`, `loading`.
- `handleFile()`: reads uploaded file via FileReader and sets `headers` and `rows`.
- `buildPreview()`: builds a preview of mapped rows (first 20) so users can validate mapping.
- `submit()`: maps CSV rows to transaction objects and POSTs to `POST /integrations/{id}/import`.
- UI layout: file input, mapping dropdowns for each CSV header, preview table, and action buttons.

Why this design
- Keeps controller thin: frontend performs CSV parsing and mapping, sends canonical JSON to the existing import endpoint.
- Background processing: import is enqueued via `TallyImportJob` so heavy work happens on the queue.
- Usability: preview reduces import mistakes.

Testing procedure (manual)
1. Navigate to Integrations page as a user with `Manage integrations` permission.
2. Ensure an integration exists for your team.
3. Click `Import CSV` for the integration.
4. Upload a CSV file with headers (example below).
5. Map columns: choose which CSV column maps to `Reference`, `Amount`, `Date`, etc.
6. Click `Preview` and inspect the first rows for correctness.
7. Click `Start Import`. You should receive `202 Accepted` and the job will be queued.
8. Verify the job executed via `php artisan queue:work` or Horizon and check logs.

Sample CSV

reference,amount,date,description,account
T1,100,2020-01-01,Payment,Sales
T2,250,2020-01-02,Refund,Sales

Expected result
- Preview shows objects like `{ ref: 'T1', amount: '100', date: '2020-01-01', description: 'Payment', account: 'Sales' }`.
- The import endpoint returns 202 and a job is dispatched.

Automated tests included
- Backend controller tests already assert job dispatch when sending a JSON `transactions` payload: `tests/Feature/IntegrationJobDispatchTest.php`.

Troubleshooting (browser)
- If preview is empty: ensure the CSV has a header row and rows following it.
- If import fails with 422: check mapping ensures required fields (e.g., amount) are present and non-empty.
- For large files: prefer chunked imports (future improvement). Current UI is intended for small-to-medium CSVs.

Maintenance notes for junior devs
- CSV parser is intentionally small; to support complex CSVs, integrate a robust CSV parser (e.g., `papaparse`) and update `parseCSV()`.
- Mapping UI posts canonical JSON to `integrations.import`; controller remains thin and reuses `TallyImportJob`.
- To support column transforms (e.g., parse localized numbers/dates), add transform hooks in the UI before submission or add a server-side mapping layer.

Verification checklist
- [ ] Upload sample CSV and preview mappings.
- [ ] Submit import and verify `TallyImportJob` was dispatched (check logs or queue).
- [ ] Test unauthorized user cannot access import UI.

*** End of document ***
