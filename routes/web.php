<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/documents/upload', [\App\Http\Controllers\DocumentController::class, 'page'])
        ->name('documents.upload');
    Route::post('/documents', [\App\Http\Controllers\DocumentController::class, 'store'])
        ->name('documents.store');
    Route::get('/documents', [\App\Http\Controllers\DocumentController::class, 'index'])
        ->name('documents.index');

    Route::prefix('team/members')
        ->name('team.members.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Team\TeamUserController::class, 'page'])
                ->name('page');
            Route::get('/list', [\App\Http\Controllers\Team\TeamUserController::class, 'index'])
                ->name('index');
            Route::post('/', [\App\Http\Controllers\Team\TeamUserController::class, 'store'])
                ->name('store');
            Route::put('/{user}', [\App\Http\Controllers\Team\TeamUserController::class, 'update'])
                ->name('update');
            Route::delete('/{user}', [\App\Http\Controllers\Team\TeamUserController::class, 'destroy'])
                ->name('destroy');
        });

    Route::prefix('transactions')
        ->name('transactions.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Transactions\TransactionController::class, 'index'])
                ->name('index');
            Route::get('/list', [\App\Http\Controllers\Transactions\TransactionController::class, 'list'])
                ->name('list');
            Route::get('/export', [\App\Http\Controllers\Transactions\TransactionController::class, 'export'])
                ->name('export');
        });

    Route::prefix('search')
        ->name('search.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Search\SearchController::class, 'page'])
                ->name('page');
            Route::get('/transactions', [\App\Http\Controllers\Search\SearchController::class, 'transactions'])
                ->name('transactions');
        });

    Route::prefix('audit/logs')
        ->name('audit.logs.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Audit\AuditLogController::class, 'page'])
                ->name('page');
            Route::get('/list', [\App\Http\Controllers\Audit\AuditLogController::class, 'list'])
                ->name('list');
        });
    
    // Invoices
    Route::prefix('invoices')
        ->name('invoices.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Invoices\InvoiceController::class, 'page'])
                ->name('page');
            Route::get('/list', [\App\Http\Controllers\Invoices\InvoiceController::class, 'list'])
                ->name('list');
        });

    // Receipts
    Route::prefix('receipts')
        ->name('receipts.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Receipts\ReceiptController::class, 'page'])
                ->name('page');
            Route::get('/list', [\App\Http\Controllers\Receipts\ReceiptController::class, 'list'])
                ->name('list');
        });

    // Contracts
    Route::prefix('contracts')
        ->name('contracts.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Contracts\ContractController::class, 'page'])
                ->name('page');
            Route::get('/list', [\App\Http\Controllers\Contracts\ContractController::class, 'list'])
                ->name('list');
        });

    // Integrations
    Route::prefix('integrations')
        ->name('integrations.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Integrations\IntegrationController::class, 'page'])
                ->name('page');
            Route::get('/list', [\App\Http\Controllers\Integrations\IntegrationController::class, 'list'])
                ->name('list');
            Route::post('/', [\App\Http\Controllers\Integrations\IntegrationController::class, 'store'])
                ->name('store');
            Route::post('/test-connection', [\App\Http\Controllers\Integrations\IntegrationController::class, 'testConnection'])
                ->name('test_connection');
            Route::post('/{integration}/import', [\App\Http\Controllers\Integrations\IntegrationController::class, 'import'])
                ->name('import');
            Route::post('/{integration}/export', [\App\Http\Controllers\Integrations\IntegrationController::class, 'export'])
                ->name('export');
            Route::get('/{integration}/exports', [\App\Http\Controllers\Integrations\IntegrationController::class, 'exportsList'])
                ->name('exports.list');
            Route::get('/{integration}/exports/{filename}', [\App\Http\Controllers\Integrations\IntegrationController::class, 'downloadExport'])
                ->name('exports.download');
            Route::get('/{integration}/export-status', [\App\Http\Controllers\Integrations\IntegrationController::class, 'exportStatus'])
                ->name('export.status');
            Route::post('/{integration}/upload-session', [\App\Http\Controllers\Integrations\IntegrationController::class, 'createUploadSession'])
                ->name('upload.session.create');
            Route::put('/{integration}/upload-chunk', [\App\Http\Controllers\Integrations\IntegrationController::class, 'uploadChunk'])
                ->name('upload.chunk');
            Route::post('/{integration}/upload-finalize', [\App\Http\Controllers\Integrations\IntegrationController::class, 'finalizeUpload'])
                ->name('upload.finalize');
            Route::get('/{integration}/upload-resume', [\App\Http\Controllers\Integrations\IntegrationController::class, 'resumeUpload'])
                ->name('upload.resume');
            Route::get('/{integration}/upload-scan-status', [\App\Http\Controllers\Integrations\IntegrationController::class, 'uploadScanStatus'])
                ->name('upload.scan-status');
            Route::put('/{integration}', [\App\Http\Controllers\Integrations\IntegrationController::class, 'update'])
                ->name('update');
            Route::delete('/{integration}', [\App\Http\Controllers\Integrations\IntegrationController::class, 'destroy'])
                ->name('destroy');

            Route::get('/quickbooks/connect', [\App\Http\Controllers\Integrations\IntegrationController::class, 'connectQuickBooks'])
                ->name('quickbooks.connect');
            Route::get('/quickbooks/callback', [\App\Http\Controllers\Integrations\IntegrationController::class, 'quickBooksCallback'])
                ->name('quickbooks.callback');

            Route::get('/xero/connect', [\App\Http\Controllers\Integrations\IntegrationController::class, 'connectXero'])
                ->name('xero.connect');
            Route::get('/xero/callback', [\App\Http\Controllers\Integrations\IntegrationController::class, 'xeroCallback'])
                ->name('xero.callback');

            Route::get('/zoho/connect', [\App\Http\Controllers\Integrations\IntegrationController::class, 'connectZoho'])
                ->name('zoho.connect');
            Route::get('/zoho/callback', [\App\Http\Controllers\Integrations\IntegrationController::class, 'zohoCallback'])
                ->name('zoho.callback');
            
            Route::get('/tally/connect', [\App\Http\Controllers\Integrations\IntegrationController::class, 'connectTally'])
                ->name('tally.connect');
            Route::get('/tally/callback', [\App\Http\Controllers\Integrations\IntegrationController::class, 'tallyCallback'])
                ->name('tally.callback');
        });

    // Billing
    Route::prefix('billing')
        ->name('billing.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Billing\SubscriptionController::class, 'page'])
                ->name('page');
            Route::get('/plans', [\App\Http\Controllers\Billing\SubscriptionController::class, 'plans'])
                ->name('plans');
            Route::post('/subscriptions', [\App\Http\Controllers\Billing\SubscriptionController::class, 'store'])
                ->name('subscriptions.store');
            Route::post('/payments/{paymentAttempt}/refund', [\App\Http\Controllers\Billing\SubscriptionController::class, 'refund'])
                ->middleware('can:manage_billing')
                ->name('payments.refund');
        });

    // Queue monitoring
    Route::prefix('queue')
        ->name('queue.')
        ->middleware('can:viewAdmin')
        ->group(function () {
            Route::get('/status', [\App\Http\Controllers\Queue\QueueStatusController::class, 'page'])
                ->name('status.page');
            Route::get('/status/metrics', [\App\Http\Controllers\Queue\QueueStatusController::class, 'index'])
                ->name('status.index');
        });

    // AI Assistant
    Route::prefix('ai')
        ->name('ai.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Ai\AiController::class, 'page'])
                ->name('page');
            Route::get('/list', [\App\Http\Controllers\Ai\AiController::class, 'list'])
                ->name('list');
            Route::post('/create', [\App\Http\Controllers\Ai\AiController::class, 'create'])
                ->middleware('throttle:ai')
                ->name('create');
            Route::post('/settings', [\App\Http\Controllers\Ai\AiController::class, 'updateSettings'])
                ->name('settings.update');
        });
});

require __DIR__.'/auth.php';

Route::post('/billing/webhooks/{provider}', \App\Http\Controllers\Billing\PaymentWebhookController::class)
    ->whereIn('provider', ['stripe', 'razorpay', 'paypal'])
    ->name('billing.webhooks');

use App\Http\Controllers\Admin\FaultLogController;
use App\Http\Controllers\Admin\FailedJobController;

Route::prefix('admin')->middleware(['auth', 'verified', 'can:viewAdmin'])->group(function () {
    Route::get('system-health', [\App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('admin.system_health.index');

    Route::get('fault-logs', [FaultLogController::class, 'index'])->name('admin.fault_logs.index');
    Route::get('fault-logs/{id}', [FaultLogController::class, 'show'])->name('admin.fault_logs.show');
    Route::delete('fault-logs/{id}', [FaultLogController::class, 'destroy'])->name('admin.fault_logs.destroy');

    Route::get('failed-jobs', [FailedJobController::class, 'index'])->name('admin.failed_jobs.index');
    Route::get('failed-jobs/{id}', [FailedJobController::class, 'show'])->name('admin.failed_jobs.show');
    Route::post('failed-jobs/{id}/retry', [FailedJobController::class, 'retry'])->name('admin.failed_jobs.retry');
    Route::delete('failed-jobs/{id}', [FailedJobController::class, 'destroy'])->name('admin.failed_jobs.destroy');
});
