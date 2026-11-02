# app/Jobs/ProcessDocumentJob.php

## Purpose

Handle document processing asynchronously after upload.

## Business Reason

Processing documents in the background preserves responsiveness and allows long-running extraction tasks to run without blocking the request cycle.

## Architecture

- Implements `ShouldQueue`.
- Uses `Dispatchable`, `InteractsWithQueue`, `Queueable`, and `SerializesModels`.
- Receives the `Document` model and updates its status.

## Flow

1. The job receives a `Document` instance.
2. `handle()` sets the status to `processing`.
3. Placeholder logic runs for extraction or queue orchestration.
4. The document status becomes `processed`.

## Why this is useful

This makes upload fast for the user and ensures the document pipeline can continue on a worker.
