# 06 - Document Upload Verification Checklist

## Feature Scope

- [x] Upload page exists and renders.
- [x] PDF upload is validated and stored.
- [x] Uploaded documents are tenant-scoped.
- [x] Background document processing job is dispatched.
- [x] Upload page uses authenticated dashboard layout.
- [x] Tests cover upload access and processing dispatch.

## Manual Verification

- [ ] Log in as a user with `create_documents` permission.
- [ ] Navigate to `/documents/upload`.
- [ ] Confirm the page title is `Upload Document`.
- [ ] Enter `Bank Statement July` and select a PDF file.
- [ ] Click `Upload Document`.
- [ ] Confirm network request `POST /documents` is successful.
- [ ] Confirm the uploaded file stores under `public/tenant-{id}/documents/`.
- [ ] Confirm the response status is `201 Created`.
- [ ] Confirm the browser reloads and the form resets.

## Test Verification

- [x] `php artisan test --filter=DocumentUploadTest`
- [x] `php artisan test --filter=DocumentProcessingTest`

## Documentation Verification

- [x] `docs/features/06-document-upload.md`
- [x] `docs/features/06-document-upload-frontend.md`
- [x] `docs/features/06-document-upload-checklist.md`
- [x] `docs/directories/app-http-controllers-documentcontroller.md`
- [x] `docs/directories/app-actions-documents-uploaddocumentaction.md`
- [x] `docs/directories/app-jobs-processdocumentjob.md`
- [x] `docs/directories/app-http-requests-storedocumentrequest.md`
- [x] `docs/directories/resources-js-pages-documents-upload.md`
- [x] `docs/directories/tests-feature-documentuploadtest.md`
