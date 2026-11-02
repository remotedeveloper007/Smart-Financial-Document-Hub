<?php

namespace App\Scopes;

use App\Services\Tenancy\TeamContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelongsToTeamScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $teamId = app(TeamContext::class)->teamId();

        $builder->where($model->getTable().'.team_id', $teamId);
    }
}
