<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('verification.notice'))->assertOk();
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('permissions', ['slug' => Permission::PERMISSION_VIEW_TRANSACTIONS]);
        $this->assertTrue(
            $user->roles()->where('team_id', $user->team_id)->with('permissions')
                ->get()->flatMap(fn ($role) => $role->permissions->pluck('slug'))
                ->contains(Permission::PERMISSION_VIEW_TRANSACTIONS),
            'Owner role should be linked to view_transactions in the database.',
        );
        $this->assertTrue($user->hasRole('owner'), 'New registrant should receive the Owner role.');
        $this->assertTrue(
            $user->hasPermission(Permission::PERMISSION_VIEW_TRANSACTIONS),
            'New Owner should receive view_transactions.',
        );
        $this->assertTrue(
            $user->hasPermission(Permission::PERMISSION_MANAGE_AI_SETTINGS),
            'New Owner should receive manage_ai_settings.',
        );
        $this->assertFalse($user->hasPermission(Permission::PERMISSION_EXPORT_TRANSACTIONS));
    }

    public function test_repeated_team_names_receive_unique_generated_slugs(): void
    {
        $action = app(RegisterUserAction::class);
        $firstUser = $action->execute([
            'name' => 'Same Name',
            'email' => 'first@example.com',
            'password' => 'password',
        ]);
        $secondUser = $action->execute([
            'name' => 'Same Name',
            'email' => 'second@example.com',
            'password' => 'password',
        ]);

        $firstTeam = $firstUser->team()->firstOrFail();
        $secondTeam = $secondUser->team()->firstOrFail();

        $this->assertNotSame($firstTeam->slug, $secondTeam->slug);
        $this->assertSame(2, \App\Models\Team::query()
            ->whereIn('id', [$firstTeam->id, $secondTeam->id])
            ->whereIn('slug', [$firstTeam->slug, $secondTeam->slug])
            ->count());
    }
}
