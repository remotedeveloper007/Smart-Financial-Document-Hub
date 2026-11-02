<?php

namespace App\Listeners;

use App\Actions\Audit\RecordAuditLogAction;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;

final class RecordAuthEventListener
{
    public function __construct(private RecordAuditLogAction $recordAuditLogAction)
    {
    }

    public function handle($event): void
    {
        $teamId = null;
        $userId = null;

        if (property_exists($event, 'user')) {
            $user = $event->user;
            $userId = $user->id ?? null;
            $teamId = $user->team_id ?? $user->teams()->value('teams.id');
        }

        $action = 'auth.event';
        $message = '';

        switch (get_class($event)) {
            case Login::class:
                $action = 'auth.login';
                $message = 'User logged in';
                break;
            case Logout::class:
                $action = 'auth.logout';
                $message = 'User logged out';
                break;
            case Registered::class:
                $action = 'auth.registered';
                $message = 'User registered';
                break;
            case PasswordReset::class:
                $action = 'auth.password_reset';
                $message = 'User password reset';
                break;
        }

        try {
            $this->recordAuditLogAction->execute(
                $teamId,
                $userId,
                $action,
                null,
                null,
                null,
                'info',
                $message,
                null,
                request()?->ip(),
            );
        } catch (\Throwable $e) {
            // swallow
        }
    }
}
