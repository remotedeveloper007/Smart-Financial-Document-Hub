<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Permission;
use App\Models\Team;
use App\Services\Tenancy\TeamContext;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $teamContext = app(TeamContext::class);
        $permissions = $user === null
            ? []
            : array_values(array_filter(
                array_keys(Permission::defaultPermissions()),
                fn (string $permission): bool => $user->hasPermission($permission),
            ));

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'tenant' => [
                'activeTeam' => $teamContext->hasTeam()
                    ? $teamContext->getTeam()->only(['id', 'name'])
                    : null,
                'teams' => $user === null ? [] : $this->teamsFor($user),
            ],
            'can' => [
                'isPlatformAdmin' => $user !== null && Gate::allows('viewPlatformAdmin'),
                'viewAdmin' => $user !== null && Gate::allows('viewAdmin'),
                'permissions' => $permissions,
            ],
        ];
    }

    /**
     * Return only teams the authenticated user is allowed to select.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function teamsFor(\App\Models\User $user): array
    {
        $teams = $user->teams()->get(['teams.id', 'teams.name']);
        $primaryTeam = $user->team;

        if ($primaryTeam !== null && ! $teams->contains('id', $primaryTeam->id)) {
            $teams->prepend($primaryTeam);
        }

        return $teams
            ->unique('id')
            ->map(fn (Team $team): array => [
                'id' => (int) $team->id,
                'name' => $team->name,
            ])
            ->values()
            ->all();
    }
}
