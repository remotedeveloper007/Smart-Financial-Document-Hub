<?php

namespace App\Actions\Team;

use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;

final class CreateTeamMemberAction
{
    public function __construct(private readonly Hasher $hash)
    {
    }

    public function execute(Team $team, array $data): User
    {
        $user = User::query()->firstWhere('email', $data['email']);

        if ($user === null) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $this->hash->make(Str::random(24)),
                'team_id' => $team->id,
            ]);
        }

        if ($user->team_id === null) {
            $user->team_id = $team->id;
            $user->save();
        }

        if (! $team->hasMember($user)) {
            $team->addMember($user);
        }

        if (isset($data['role_slug'])) {
            $this->assignRole($team, $user, $data['role_slug']);
        }

        return $user->refresh();
    }

    private function assignRole(Team $team, User $user, string $roleSlug): void
    {
        $role = Role::query()
            ->where('team_id', $team->id)
            ->where('slug', $roleSlug)
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
