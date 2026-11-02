<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\Integrations\QuickBooksOAuthService;
use App\Services\Integrations\XeroOAuthService;
use App\Services\Integrations\ZohoBooksOAuthService;
use App\Services\Tenancy\TeamContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Jobs\TallyImportJob;
use App\Jobs\TallyExportJob;
use App\Jobs\TallyImportFromFileJob;

final class IntegrationController extends Controller
{
    public function page(): Response
    {
        $this->authorize('viewAny', Integration::class);

        return Inertia::render('Integrations/Index');
    }

    public function list(TeamContext $teamContext): JsonResponse
    {
        $this->authorize('viewAny', Integration::class);

        $integrations = Integration::query()
            ->where('team_id', $teamContext->teamId())
            ->orderBy('provider')
            ->paginate(20);

        return response()->json(['integrations' => $integrations]);
    }

    public function store(Request $request, TeamContext $teamContext): JsonResponse
    {
        $this->authorize('create', Integration::class);

        $validated = $request->validate([
            'provider' => ['required', 'string', 'max:120', Rule::unique('integrations')->where(fn ($query) => $query->where('team_id', $teamContext->teamId()))],
            'external_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:enabled,disabled'],
            'credentials' => ['nullable', 'array'],
        ]);

        $integration = Integration::create([
            'team_id' => $teamContext->teamId(),
            'provider' => $validated['provider'],
            'external_id' => $validated['external_id'] ?? null,
            'status' => $validated['status'],
            'credentials' => $validated['credentials'] ?? [],
        ]);

        return response()->json(['integration' => $integration], 201);
    }

    public function update(Request $request, TeamContext $teamContext, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            throw new ModelNotFoundException();
        }

        $validated = $request->validate([
            'provider' => ['required', 'string', 'max:120', Rule::unique('integrations')->where(fn ($query) => $query->where('team_id', $teamContext->teamId()))->ignore($integration->id)],
            'external_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:enabled,disabled'],
            'credentials' => ['nullable', 'array'],
        ]);

        $integration->fill([
            'provider' => $validated['provider'],
            'external_id' => $validated['external_id'] ?? null,
            'status' => $validated['status'],
            'credentials' => array_replace(
                (array) ($integration->credentials ?? []),
                $validated['credentials'] ?? [],
            ),
        ]);
        $integration->save();

        return response()->json(['integration' => $integration]);
    }

    public function destroy(TeamContext $teamContext, Integration $integration): JsonResponse
    {
        $this->authorize('delete', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            throw new ModelNotFoundException();
        }

        $integration->delete();

        return response()->json([], 204);
    }

    public function connectQuickBooks(TeamContext $teamContext, QuickBooksOAuthService $quickBooksOAuthService): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        $state = (string) Str::uuid();
        session(['quickbooks_oauth_state' => $state]);

        $url = $quickBooksOAuthService->buildAuthorizationUrl($state);

        return redirect()->away($url);
    }

    public function quickBooksCallback(Request $request, TeamContext $teamContext, QuickBooksOAuthService $quickBooksOAuthService): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        $state = $request->query('state');
        $code = $request->query('code');

        if (! $state || $state !== session('quickbooks_oauth_state')) {
            abort(419, 'Invalid OAuth state.');
        }

        $tokens = $quickBooksOAuthService->exchangeCodeForTokens((string) $code);

        $integration = Integration::firstOrNew([
            'team_id' => $teamContext->teamId(),
            'provider' => 'quickbooks',
        ]);

        $integration->fill([
            'external_id' => $tokens['realmId'] ?? null,
            'status' => 'enabled',
            'credentials' => [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_type' => $tokens['token_type'] ?? 'bearer',
                'expires_in' => $tokens['expires_in'] ?? null,
            ],
        ]);
        $integration->save();

        session()->forget('quickbooks_oauth_state');

        return redirect()->route('integrations.page');
    }

    public function connectXero(TeamContext $teamContext, XeroOAuthService $xeroOAuthService): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        $state = (string) Str::uuid();
        session(['xero_oauth_state' => $state]);

        $url = $xeroOAuthService->buildAuthorizationUrl($state);

        return redirect()->away($url);
    }

    public function xeroCallback(Request $request, TeamContext $teamContext, XeroOAuthService $xeroOAuthService): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        $state = $request->query('state');
        $code = $request->query('code');

        if (! $state || $state !== session('xero_oauth_state')) {
            abort(419, 'Invalid OAuth state.');
        }

        $tokens = $xeroOAuthService->exchangeCodeForTokens((string) $code);

        $integration = Integration::firstOrNew([
            'team_id' => $teamContext->teamId(),
            'provider' => 'xero',
        ]);

        $integration->fill([
            'external_id' => $tokens['xero_tenant_id'] ?? $tokens['tenant_id'] ?? null,
            'status' => 'enabled',
            'credentials' => [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_type' => $tokens['token_type'] ?? 'Bearer',
                'expires_in' => $tokens['expires_in'] ?? null,
            ],
        ]);
        $integration->save();

        session()->forget('xero_oauth_state');

        return redirect()->route('integrations.page');
    }

    public function connectZoho(TeamContext $teamContext, ZohoBooksOAuthService $zohoBooksOAuthService): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        $state = (string) Str::uuid();
        session(['zoho_oauth_state' => $state]);

        $url = $zohoBooksOAuthService->buildAuthorizationUrl($state);

        return redirect()->away($url);
    }

    public function zohoCallback(Request $request, TeamContext $teamContext, ZohoBooksOAuthService $zohoBooksOAuthService): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        $state = $request->query('state');
        $code = $request->query('code');

        if (! $state || $state !== session('zoho_oauth_state')) {
            abort(419, 'Invalid OAuth state.');
        }

        $tokens = $zohoBooksOAuthService->exchangeCodeForTokens((string) $code);

        $integration = Integration::firstOrNew([
            'team_id' => $teamContext->teamId(),
            'provider' => 'zoho',
        ]);

        $integration->fill([
            'external_id' => $tokens['organization_id'] ?? $tokens['realmId'] ?? null,
            'status' => 'enabled',
            'credentials' => [
                'access_token' => $tokens['access_token'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_type' => $tokens['token_type'] ?? 'Bearer',
                'expires_in' => $tokens['expires_in'] ?? null,
                'api_domain' => $tokens['api_domain'] ?? null,
            ],
        ]);
        $integration->save();

        session()->forget('zoho_oauth_state');

        return redirect()->route('integrations.page');
    }

    public function connectTally(): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        // Tally typically uses API keys / tokens rather than a redirect-based OAuth flow.
        // Provide a simple redirect to the integrations page where users can add their Tally credentials.
        return redirect()->route('integrations.page');
    }

    public function tallyCallback(Request $request, TeamContext $teamContext): RedirectResponse
    {
        $this->authorize('create', Integration::class);

        // Placeholder: if Tally ever provides an OAuth-like callback, handle token exchange here.
        // For now simply redirect back to the integrations page.
        return redirect()->route('integrations.page');
    }

    public function testConnection(Request $request, \App\Services\Integrations\TallyService $tallyService): JsonResponse
    {
        $this->authorize('create', Integration::class);

        $validated = $request->validate([
            'credentials' => ['required', 'array'],
        ]);

        $ok = $tallyService->testConnection($validated['credentials']);

        return response()->json(['ok' => $ok]);
    }

    public function import(TeamContext $teamContext, Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $validated = $request->validate([
            'transactions' => ['nullable', 'array'],
            'assembled_path' => ['nullable', 'string'],
        ]);

        // If frontend posts an assembled file path (tenant-scoped), dispatch file-based import job
        if (! empty($validated['assembled_path'])) {
            $assembledPath = $validated['assembled_path'];
            $assembledPrefix = "tenant-{$teamContext->teamId()}/uploads/assembled/";

            if (
                ! str_starts_with($assembledPath, $assembledPrefix)
                || str_contains($assembledPath, '..')
                || str_contains($assembledPath, '\\')
            ) {
                abort(404);
            }

            $fileName = substr($assembledPath, strlen($assembledPrefix));
            if (! preg_match('/\A([0-9a-f-]{36})_(.+\.csv)\z/i', $fileName, $matches)) {
                abort(404);
            }

            $sessionId = $matches[1];
            $sessionMetaPath = "tenant-{$teamContext->teamId()}/uploads/sessions/{$sessionId}.json";
            $disk = Storage::disk('local');
            if (! $disk->exists($sessionMetaPath) || ! $disk->exists($assembledPath)) {
                abort(404);
            }

            $sessionMetadata = json_decode($disk->get($sessionMetaPath), true);
            if (
                ! is_array($sessionMetadata)
                || (int) ($sessionMetadata['team_id'] ?? 0) !== $teamContext->teamId()
                || (int) ($sessionMetadata['integration_id'] ?? 0) !== $integration->id
                || ($sessionMetadata['filename'] ?? null) !== $matches[2]
            ) {
                abort(404);
            }

            $scanMetadataPath = $assembledPath.'.scan.json';
            if (! $disk->exists($scanMetadataPath)) {
                return response()->json(['error' => 'scan_not_complete'], 409);
            }

            $scanMetadata = json_decode($disk->get($scanMetadataPath), true);
            if (
                ! is_array($scanMetadata)
                || ($scanMetadata['status'] ?? null) !== 'clean'
                || ($scanMetadata['scanner'] ?? null) !== 'clamav'
                || ($scanMetadata['path'] ?? null) !== $assembledPath
            ) {
                return response()->json(['error' => 'scan_not_clean'], 409);
            }

            $contents = $disk->get($assembledPath);
            $checksum = (string) ($sessionMetadata['checksum'] ?? '');
            if (
                strlen($contents) !== (int) ($sessionMetadata['size'] ?? -1)
                || (! hash_equals($checksum, md5($contents)) && ! hash_equals($checksum, hash('sha256', $contents)))
            ) {
                return response()->json(['error' => 'upload_integrity_check_failed'], 409);
            }

            TallyImportFromFileJob::dispatch($integration->id, $teamContext->teamId(), $assembledPath);
            return response()->json(['accepted' => true], 202);
        }

        if (! empty($validated['transactions'])) {
            TallyImportJob::dispatch($integration->id, $teamContext->teamId(), $validated['transactions']);
            return response()->json(['accepted' => true], 202);
        }

        return response()->json(['error' => 'no_payload'], 422);
    }

    public function export(TeamContext $teamContext, Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('view', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $filters = $request->input('filters', null);

        TallyExportJob::dispatch($integration->id, $teamContext->teamId(), $filters);

        return response()->json(['accepted' => true], 202);
    }

    public function exportsList(TeamContext $teamContext, Integration $integration): JsonResponse
    {
        $this->authorize('view', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $files = [];
        $prefix = 'tenant-' . $teamContext->teamId() . '/exports/tally';
        $all = Storage::disk('local')->files($prefix);
        foreach ($all as $path) {
            if (str_contains(basename($path), "_integration_{$integration->id}_")) {
                $files[] = [
                    'path' => $path,
                    'name' => basename($path),
                    'url' => route('integrations.exports.download', ['integration' => $integration->id, 'filename' => basename($path)]),
                ];
            }
        }

        return response()->json(['files' => $files]);
    }

    public function downloadExport(TeamContext $teamContext, Integration $integration, string $filename)
    {
        $this->authorize('view', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $path = 'tenant-' . $teamContext->teamId() . '/exports/tally/' . basename($filename);

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path);
    }

    public function exportStatus(TeamContext $teamContext, Integration $integration): JsonResponse
    {
        $this->authorize('view', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $status = Cache::get("integration_export_status:team:{$teamContext->teamId()}:integration:{$integration->id}", null);

        return response()->json(['status' => $status]);
    }

    public function createUploadSession(TeamContext $teamContext, Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $validated = $request->validate([
            'filename' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail): void {
                    if (
                        basename(str_replace('\\', '/', (string) $value)) !== $value
                        || strtolower(pathinfo((string) $value, PATHINFO_EXTENSION)) !== 'csv'
                        || preg_match('/[\x00-\x1F\x7F]/', (string) $value) === 1
                    ) {
                        $fail('The filename must be a plain CSV filename.');
                    }
                },
            ],
            'checksum' => ['required', 'string', 'regex:/\A(?:[a-fA-F0-9]{32}|[a-fA-F0-9]{64})\z/'],
            'size' => ['required', 'integer', 'min:1', 'max:104857600'],
        ]);

        $filename = (string) $validated['filename'];

        $sessionId = (string) Str::uuid();
        $meta = [
            'filename' => $filename,
            'checksum' => $validated['checksum'],
            'size' => $validated['size'],
            'integration_id' => $integration->id,
            'team_id' => $teamContext->teamId(),
        ];

        $tenantPrefix = 'tenant-' . $teamContext->teamId();
        Storage::disk('local')->put("{$tenantPrefix}/uploads/sessions/{$sessionId}.json", json_encode($meta));

        return response()->json(['session_id' => $sessionId]);
    }

    public function uploadChunk(TeamContext $teamContext, Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:1'],
            'chunk' => [],
            'chunk_checksum' => ['nullable', 'string'],
        ]);

        $sessionId = $validated['session_id'];
        $this->uploadSessionMetadata($sessionId, $teamContext->teamId(), $integration->id);

        $tenantPrefix = 'tenant-' . $teamContext->teamId();
        $dir = "{$tenantPrefix}/uploads/tmp/{$sessionId}";
        if (! Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }

        $index = $validated['index'];

        // Accept file uploads or JSON/base64 payloads in `chunk`
        $chunkChecksum = $validated['chunk_checksum'] ?? null;
        $stored = false;

        if ($request->hasFile('chunk')) {
            $file = $request->file('chunk');
            Storage::disk('local')->putFileAs($dir, $file, "chunk_{$index}");
            $contents = Storage::disk('local')->get("{$dir}/chunk_{$index}");
            $computed = md5($contents);
            $stored = true;
        } else {
            $body = $request->input('chunk');
            if (is_string($body) && base64_decode($body, true) !== false) {
                $contents = base64_decode($body);
                Storage::disk('local')->put("{$dir}/chunk_{$index}", $contents);
                $computed = md5($contents);
                $stored = true;
            } else {
                // attempt raw body
                $contents = $request->getContent();
                if (!empty($contents)) {
                    Storage::disk('local')->put("{$dir}/chunk_{$index}", $contents);
                    $computed = md5($contents);
                    $stored = true;
                }
            }
        }

        if (! $stored) {
            return response()->json(['ok' => false, 'error' => 'no_chunk_provided'], 422);
        }

        // compute both md5 and sha256 for compatibility
        $computedMd5 = $computed;
        $computedSha256 = hash('sha256', $contents);

        // validate per-chunk checksum if provided (accept md5 or sha256)
        if ($chunkChecksum && $chunkChecksum !== $computedMd5 && $chunkChecksum !== $computedSha256) {
            // remove bad chunk
            Storage::disk('local')->delete("{$dir}/chunk_{$index}");
            return response()->json(['ok' => false, 'error' => 'chunk_checksum_mismatch'], 422);
        }

        // write metadata for chunk (store both checksums)
        Storage::disk('local')->put("{$dir}/chunk_{$index}.meta.json", json_encode(['index' => $index, 'md5' => $computedMd5, 'sha256' => $computedSha256]));

        $computed = $computedMd5;

        return response()->json(['ok' => true, 'index' => $index, 'checksum' => $computed]);
    }

    public function resumeUpload(TeamContext $teamContext, Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
        ]);

        $sessionId = $validated['session_id'];
        $this->uploadSessionMetadata($sessionId, $teamContext->teamId(), $integration->id);
        $tenantPrefix = 'tenant-' . $teamContext->teamId();
        $dir = "{$tenantPrefix}/uploads/tmp/{$sessionId}";
        if (! Storage::disk('local')->exists($dir)) {
            return response()->json(['received' => []]);
        }

        $files = Storage::disk('local')->files($dir);
        $received = [];
        foreach ($files as $f) {
            if (str_ends_with($f, '.meta.json')) {
                $m = json_decode(Storage::disk('local')->get($f), true);
                $received[] = $m;
            }
        }

        usort($received, fn($a, $b) => $a['index'] <=> $b['index']);

        return response()->json(['received' => $received]);
    }

    public function finalizeUpload(TeamContext $teamContext, Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
        ]);

        $sessionId = $validated['session_id'];
        $tenantPrefix = 'tenant-' . $teamContext->teamId();
        $meta = $this->uploadSessionMetadata($sessionId, $teamContext->teamId(), $integration->id);
        $dir = "{$tenantPrefix}/uploads/tmp/{$sessionId}";
        $allFiles = Storage::disk('local')->files($dir);

        // select only chunk files (ignore .meta.json) and sort by numeric index
        $chunkFiles = [];
        foreach ($allFiles as $f) {
            if (preg_match('/chunk_(\d+)$/', $f, $m)) {
                $chunkFiles[(int)$m[1]] = $f;
            }
        }
        ksort($chunkFiles, SORT_NUMERIC);

        $assembledPath = "{$tenantPrefix}/uploads/assembled/{$sessionId}_" . $meta['filename'];

        $assembledContent = '';
        foreach ($chunkFiles as $f) {
            $contents = Storage::disk('local')->get($f);
            $assembledContent .= $contents;
        }

        Storage::disk('local')->put($assembledPath, $assembledContent);

        if (strlen($assembledContent) !== (int) $meta['size']) {
            Storage::disk('local')->delete($assembledPath);

            return response()->json(['ok' => false, 'error' => 'size_mismatch'], 422);
        }

        $computedMd5 = md5($assembledContent);
        $computedSha256 = hash('sha256', $assembledContent);

        if ($meta['checksum'] !== $computedMd5 && $meta['checksum'] !== $computedSha256) {
            // cleanup
            Storage::disk('local')->delete($assembledPath);
            return response()->json(['ok' => false, 'error' => 'checksum_mismatch'], 422);
        }

        // idempotency: check if file with same checksum already imported
        $checksumCacheKey = "imported_file_checksum:team:{$teamContext->teamId()}:integration:{$integration->id}:{$meta['checksum']}";
        $existing = Cache::get($checksumCacheKey);
        if ($existing) {
            return response()->json(['ok' => true, 'message' => 'already_imported']);
        }

        // Dispatch scan job to the queue so scanning happens asynchronously.
        try {
            if (app()->environment('testing')) {
                // Tests resolve the same scanner dependency as queued production jobs.
                $job = new \App\Jobs\ScanUploadedFileJob($assembledPath);
                app()->call([$job, 'handle']);
            } else {
                \App\Jobs\ScanUploadedFileJob::dispatch($assembledPath);
            }
        } catch (\Throwable $e) {
            Cache::forget($checksumCacheKey);
            return response()->json(['ok' => false, 'error' => 'scan_failed'], 500);
        }

        $scanMetadataPath = $assembledPath . '.scan.json';
        if (Storage::disk('local')->exists($scanMetadataPath)) {
            $scanMetadata = json_decode(Storage::disk('local')->get($scanMetadataPath), true);

            if (($scanMetadata['status'] ?? null) === 'quarantined') {
                Cache::put($checksumCacheKey, true, 86400);
                return response()->json(['ok' => false, 'error' => 'file_quarantined'], 422);
            }

            if (($scanMetadata['status'] ?? null) === 'clean') {
                Cache::put($checksumCacheKey, true, 86400);
                return response()->json([
                    'ok' => true,
                    'status' => 'clean',
                    'assembled_path' => $assembledPath,
                    'assembled_csv' => $assembledContent,
                    'filename' => $meta['filename'],
                ]);
            }
        }

        // Return scanning status and assembled path. Frontend should poll scanStatus.
        return response()->json(['ok' => true, 'status' => 'scanning', 'assembled_path' => $assembledPath, 'filename' => $meta['filename']]);
    }

    public function uploadScanStatus(TeamContext $teamContext, Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        if ((int) $integration->team_id !== $teamContext->teamId()) {
            abort(404);
        }

        $validated = $request->validate([
            'session_id' => ['required', 'uuid'],
        ]);

        $sessionId = $validated['session_id'];
        $tenantPrefix = 'tenant-' . $teamContext->teamId();
        $sessionMetadata = $this->uploadSessionMetadata($sessionId, $teamContext->teamId(), $integration->id);
        $assembledPath = "{$tenantPrefix}/uploads/assembled/{$sessionId}_";

        // find assembled file matching prefix
        $files = Storage::disk('local')->files("{$tenantPrefix}/uploads/assembled");
        $match = null;
        foreach ($files as $f) {
            if (str_starts_with(basename($f), "{$sessionId}_")) {
                $match = $f;
                break;
            }
        }

        if (! $match) {
            return response()->json(['status' => 'not_found']);
        }

        if (basename($match) !== "{$sessionId}_{$sessionMetadata['filename']}") {
            abort(404);
        }

        $scanMetaPath = $match . '.scan.json';
        if (! Storage::disk('local')->exists($scanMetaPath)) {
            return response()->json(['status' => 'scanning']);
        }

        $scanMeta = json_decode(Storage::disk('local')->get($scanMetaPath), true);
        if (($scanMeta['status'] ?? '') !== 'clean') {
            return response()->json(['status' => 'quarantined']);
        }

        $assembledContent = Storage::disk('local')->get($match);

        // read session meta to return original filename
        $sessionMetaPath = "{$tenantPrefix}/uploads/sessions/{$sessionId}.json";
        $origFilename = basename($match);
        if (Storage::disk('local')->exists($sessionMetaPath)) {
            $sessionMeta = json_decode(Storage::disk('local')->get($sessionMetaPath), true);
            $origFilename = $sessionMeta['filename'] ?? $origFilename;
        }

        return response()->json(['status' => 'clean', 'filename' => $origFilename, 'assembled_csv' => $assembledContent]);
    }

    /**
     * @return array<string, mixed>
     */
    private function uploadSessionMetadata(string $sessionId, int $teamId, int $integrationId): array
    {
        if (! Str::isUuid($sessionId)) {
            abort(404);
        }

        $path = "tenant-{$teamId}/uploads/sessions/{$sessionId}.json";
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $metadata = json_decode($disk->get($path), true);
        if (
            ! is_array($metadata)
            || (int) ($metadata['team_id'] ?? 0) !== $teamId
            || (int) ($metadata['integration_id'] ?? 0) !== $integrationId
            || ! is_string($metadata['filename'] ?? null)
            || ! is_int($metadata['size'] ?? null)
            || ! is_string($metadata['checksum'] ?? null)
        ) {
            abort(404);
        }

        return $metadata;
    }
}
