<?php

namespace App\Http\Controllers\Team;

use App\Actions\Team\CreateTeamMemberAction;
use App\Actions\Team\RemoveTeamMemberAction;
use App\Actions\Team\UpdateTeamMemberRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\TeamUserStoreRequest;
use App\Http\Requests\TeamUserUpdateRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TeamUserController extends Controller
{
    public function page(TeamContext $teamContext): Response
    {
        $team = $teamContext->getTeam();

        $this->authorize('manageUsers', $team);

        return Inertia::render('Team/Members');
    }

    public function index(TeamContext $teamContext): JsonResponse
    {
        $team = $teamContext->getTeam();
        
        $this->authorize('manageUsers', $team);

        $members = $team->members()
            ->with([
                'roles' => fn ($query) => $query->where('roles.team_id', $team->id),
            ])
            ->get()
            ->map(fn (User $user) => $this->serializeMember($user));

        $roles = Role::query()
            ->where('team_id', $team->id)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'members' => $members,
            'roles' => $roles,
        ]);
    }

    public function store(
        TeamUserStoreRequest $request,
        TeamContext $teamContext,
        CreateTeamMemberAction $createTeamMember,
    ): JsonResponse {
        $team = $teamContext->getTeam();

        $this->authorize('manageUsers', $team);

        $user = $createTeamMember->execute($team, $request->validated());

        return response()->json($this->serializeMember($user), 201);
    }

    public function update(
        TeamUserUpdateRequest $request,
        TeamContext $teamContext,
        User $user,
        UpdateTeamMemberRoleAction $updateTeamMemberRole,
    ): JsonResponse {
        $team = $teamContext->getTeam();

        $this->authorize('manageUsers', $team);

        if (! $team->hasMember($user)) {
            abort(404);
        }

        $updatedUser = $updateTeamMemberRole->execute(
            $team,
            $user,
            $request->validated('role_slug'),
        );

        return response()->json($this->serializeMember($updatedUser));
    }

    public function destroy(
        TeamContext $teamContext,
        User $user,
        RemoveTeamMemberAction $removeTeamMember,
    ): JsonResponse {
        $team = $teamContext->getTeam();

        $this->authorize('manageUsers', $team);

        if (! $team->hasMember($user)) {
            abort(404);
        }

        $removeTeamMember->execute($team, $user);

        return response()->json([], 204);
    }

    private function serializeMember(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ])->values(),
        ];
    }
}
