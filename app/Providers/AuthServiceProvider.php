<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\Receipt;
use App\Models\Contract;
use App\Models\Integration;
// use App\Models\AiRequest;
use App\Policies\DocumentPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ReceiptPolicy;
use App\Policies\ContractPolicy;
use App\Policies\IntegrationPolicy;
use App\Policies\AiRequestPolicy;
use App\Policies\TeamPolicy;
use App\Policies\TransactionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Get the policy mappings for the application.
     *
     * @return array<class-string, class-string>
     */
    public function policies(): array
    {
        return [
            AuditLog::class => AuditLogPolicy::class,
            Document::class => DocumentPolicy::class,
            Invoice::class => InvoicePolicy::class,
            Receipt::class => ReceiptPolicy::class,
            Contract::class => ContractPolicy::class,
            Integration::class => IntegrationPolicy::class,
               \App\Models\AiRequest::class => AiRequestPolicy::class,
            Team::class => TeamPolicy::class,
            Transaction::class => TransactionPolicy::class,
        ];
    }

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        foreach (Permission::defaultPermissions() as $slug => $_label) {
            Gate::define($slug, fn ($user) => method_exists($user, 'hasPermission') && $user->hasPermission($slug));
        }

        Gate::define('viewPlatformAdmin', fn ($user) => $user->isPlatformAdministrator());
        Gate::define('viewHorizon', fn ($user) => $user->isPlatformAdministrator());
        Gate::define('viewAdmin', fn ($user) => $user->isPlatformAdministrator());
    }
}
