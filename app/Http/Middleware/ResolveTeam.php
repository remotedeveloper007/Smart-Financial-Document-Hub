<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TeamContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTeam
{
    public function handle(Request $request, Closure $next): Response
    {
        $teamContext = app(TeamContext::class);
        $teamContext->clearTeam();

        if ($user = $request->user()) {
            if ($user->team_id !== null && $user->team !== null) {
                $teamContext->setTeam($user->team);
            } elseif ($membership = $user->teams()->first()) {
                $teamContext->setTeam($membership);
            }
        }

        return $next($request);
    }
}
