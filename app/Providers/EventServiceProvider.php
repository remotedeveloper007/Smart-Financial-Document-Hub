<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use App\Listeners\RecordAuthEventListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [RecordAuthEventListener::class],
        Logout::class => [RecordAuthEventListener::class],
        Registered::class => [RecordAuthEventListener::class],
        PasswordReset::class => [RecordAuthEventListener::class],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
