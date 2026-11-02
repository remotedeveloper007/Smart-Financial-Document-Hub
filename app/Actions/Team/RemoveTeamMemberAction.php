<?php

namespace App\Actions\Team;

use App\Models\Team;
use App\Models\User;

final class RemoveTeamMemberAction
{
    public function execute(Team $team, User $user): void
    {
        $replacement = null;

        if ($user->team_id === $team->id) {
            $replacement = $user->teams()->where('team_id', '!=', $team->id)->first();
        }

        $team->members()->detach($user->id);

        if ($user->team_id === $team->id) {
            $user->team_id = $replacement?->id;
            $user->save();
        }
    }
}
