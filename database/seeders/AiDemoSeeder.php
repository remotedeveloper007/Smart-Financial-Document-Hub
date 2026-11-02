<?php

namespace Database\Seeders;

use App\Models\AiRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

final class AiDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('AI demo data can only be seeded in local or testing environments.');
        }

        $team = Team::firstOrCreate(
            ['slug' => 'ai-demo-workspace'],
            ['name' => 'AI Demo Workspace'],
        );

        $permission = Permission::firstOrCreate(
            ['slug' => Permission::PERMISSION_USE_AI],
            ['name' => 'Use AI assistant'],
        );

        $role = Role::firstOrCreate(
            ['team_id' => $team->id, 'slug' => 'ai-demo-tester'],
            ['name' => 'AI Demo Tester'],
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $user = User::firstOrCreate(
            ['email' => 'ai.tester@local.test'],
            [
                'name' => 'AI Demo Tester',
                'password' => Hash::make('password'),
                'team_id' => $team->id,
            ],
        );

        if ((int) $user->team_id !== (int) $team->id) {
            throw new LogicException('The AI demo user already belongs to a different team; refusing to move the account.');
        }

        $team->addMember($user);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $demoRequests = [
            [
                'input' => '[AI-DEMO] Summarize synthetic test data: three transactions total 325.75 USD.',
                'response' => ['text' => 'DEMO ONLY: The supplied synthetic examples total 325.75 USD across three transactions. No application financial records were queried.'],
                'status' => AiRequest::STATUS_COMPLETED,
                'error_message' => null,
            ],
            [
                'input' => '[AI-DEMO] Identify which synthetic expense is largest.',
                'response' => null,
                'status' => AiRequest::STATUS_QUEUED,
                'error_message' => null,
            ],
            [
                'input' => '[AI-DEMO] Show how a failed synthetic request appears.',
                'response' => null,
                'status' => AiRequest::STATUS_FAILED,
                'error_message' => 'Synthetic demo failure. No provider request was made.',
            ],
        ];

        foreach ($demoRequests as $request) {
            AiRequest::withoutGlobalScopes()->updateOrCreate(
                [
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'input' => $request['input'],
                ],
                [
                    'model' => config('ai.model', 'demo-simulator'),
                    'response' => $request['response'],
                    'status' => $request['status'],
                    'error_message' => $request['error_message'],
                ],
            );
        }
    }
}
