<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SwitchActiveTeamController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'team_id' => ['required', 'integer', 'min:1'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $teamId = (int) $validated['team_id'];
        $team = $user->teams()->whereKey($teamId)->first();

        if ($team === null && (int) $user->team_id === $teamId) {
            $team = $user->team;
        }

        abort_if($team === null, 404);

        $request->session()->put('active_team_id', $team->id);

        return redirect()->back();
    }
}
