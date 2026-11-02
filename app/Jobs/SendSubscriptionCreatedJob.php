<?php

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendSubscriptionCreatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Subscription $subscription)
    {
    }

    public function middleware(): array
    {
        return [new \App\Jobs\Middleware\SetTeamContext($this->subscription->team_id)];
    }

    public function handle(): void
    {
        // Placeholder: send emails, webhook, billing provider call
        // Implementation deferred; job exists to satisfy queue-first architecture.
    }
}
