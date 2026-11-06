<?php

namespace App\Traits;

use App\Scopes\BelongsToTeamScope;
use App\Models\Team;
use App\Exceptions\TenantNotResolvedException;
use App\Services\Tenancy\TeamContext;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTeam
{
    protected static function bootBelongsToTeam(): void
    {
        static::addGlobalScope(new BelongsToTeamScope());

        static::creating(function (Model $model): void {
            $context = app(TeamContext::class);

            if ($context->hasTeam()) {
                $model->team_id = $context->teamId();

                return;
            }

            if (! isset($model->team_id)) {
                throw new TenantNotResolvedException();
            }
        });
    }
}
