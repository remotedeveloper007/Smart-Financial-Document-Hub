<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TeamContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTeam
{
    private const ACTIVE_TEAM_SESSION_KEY = 'active_team_id';

    public function handle(Request $request, Closure $next): Response
    {
        $teamContext = app(TeamContext::class);
        $teamContext->clearTeam();

        try {
            if ($user = $request->user()) {
                $teams = $user->teams()->get(['teams.id', 'teams.name']);
                $primaryTeam = $user->team;

                if ($primaryTeam !== null && ! $teams->contains('id', $primaryTeam->id)) {
                    $teams->prepend($primaryTeam);
                }

                $activeTeamId = $request->session()->get(self::ACTIVE_TEAM_SESSION_KEY);
                $activeTeam = $activeTeamId === null
                    ? null
                    : $teams->firstWhere('id', (int) $activeTeamId);

                $activeTeam ??= $teams->firstWhere('id', $user->team_id) ?? $teams->first();

                if ($activeTeam !== null) {
                    $teamContext->setTeam($activeTeam);
                    $request->session()->put(self::ACTIVE_TEAM_SESSION_KEY, $activeTeam->id);
                } else {
                    $request->session()->forget(self::ACTIVE_TEAM_SESSION_KEY);
                }
            } else {
                $request->session()->forget(self::ACTIVE_TEAM_SESSION_KEY);
            }

            return $next($request);
        } finally {
            $teamContext->clearTeam();
        }
    }
}
