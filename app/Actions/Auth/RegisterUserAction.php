<?php

namespace App\Actions\Auth;

use App\Actions\Team\CreateDefaultRolesAction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class RegisterUserAction
{
    public function __construct(private readonly CreateDefaultRolesAction $createDefaultRoles)
    {
    }

    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $team = Team::create([
                'name' => "{$user->name}'s Team",
            ]);

            $user->team()->associate($team);
            $user->save();

            $team->addMember($user);

            $roles = $this->createDefaultRoles->execute($team);

            if (isset($roles['Owner'])) {
                $user->roles()->syncWithoutDetaching([$roles['Owner']->id]);
            }

            event(new Registered($user));

            return $user;
        });
    }
}
