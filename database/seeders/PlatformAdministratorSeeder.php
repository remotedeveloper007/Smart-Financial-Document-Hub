<?php

namespace Database\Seeders;

use App\Models\PlatformAdministrator;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class PlatformAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $emails = config('system_access.admin_emails', []);
        if ($emails === []) {
            throw new LogicException('Set SYSTEM_ADMIN_EMAILS to one or more existing, verified user emails before seeding platform administrators.');
        }

        foreach ($emails as $email) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
                ->first();
            if ($user === null) {
                throw new LogicException("No existing user matches a configured SYSTEM_ADMIN_EMAILS entry. Create and verify that account before seeding.");
            }

            if (! $user->hasVerifiedEmail()) {
                throw new LogicException("A configured platform administrator account is not verified. Verify the account before seeding.");
            }

            PlatformAdministrator::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['role' => PlatformAdministrator::ROLE_SUPER_ADMIN],
            );
        }
    }
}
