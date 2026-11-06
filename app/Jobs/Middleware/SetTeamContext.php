<?php

namespace App\Jobs\Middleware;

use App\Models\Team;
use App\Exceptions\TenantNotResolvedException;
use App\Services\Tenancy\TeamContext;

final class SetTeamContext
{
    public function __construct(private ?int $teamId = null)
    {
    }

    /**
     * Set the TeamContext for the current job before handling.
     */
    public function handle($job, $next): void
    {
        $teamContext = app(TeamContext::class);

        // Always clear previous team context for long-lived workers
        $teamContext->clearTeam();

        $teamId = $this->teamId;

        if ($teamId === null) {
            if (property_exists($job, 'teamId')) {
                $teamId = $job->teamId;
            } elseif (property_exists($job, 'subscription') && optional($job->subscription)->team_id) {
                $teamId = $job->subscription->team_id;
            }
        }

        if ($teamId !== null) {
            $team = Team::find($teamId);

            if ($team === null) {
                throw new TenantNotResolvedException();
            }

            $teamContext->setTeam($team);
        }

        try {
            $next($job);
        } finally {
            $teamContext->clearTeam();
        }
    }
}
