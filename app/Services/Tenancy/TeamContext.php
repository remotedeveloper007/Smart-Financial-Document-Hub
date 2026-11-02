<?php

namespace App\Services\Tenancy;

use App\Exceptions\TenantNotResolvedException;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

final class TeamContext
{
    private ?Team $team = null;

    public function setTeam(Team $team): void
    {
        $this->team = $team;
    }

    public function clearTeam(): void
    {
        $this->team = null;
    }

    public function getTeam(): Team
    {
        if ($this->team !== null) {
            return $this->team;
        }

        if (Auth::check()) {
            $user = Auth::user();

            if ($user !== null && $user->team_id !== null) {
                $this->team = $user->team;

                if ($this->team !== null) {
                    return $this->team;
                }
            }

            if ($user !== null) {
                $membership = $user->teams()->first();

                if ($membership !== null) {
                    $this->team = $membership;

                    return $this->team;
                }
            }
        }

        throw new TenantNotResolvedException();
    }

    public function teamId(): int
    {
        return $this->getTeam()->getKey();
    }

    public function hasTeam(): bool
    {
        try {
            $this->getTeam();

            return true;
        } catch (TenantNotResolvedException) {
            return false;
        }
    }
}
