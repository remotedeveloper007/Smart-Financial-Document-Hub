<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Permission;
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
            'can' => [
                'viewAdmin' => $user !== null && Gate::allows('viewAdmin'),
                'permissions' => $permissions,
            ],
        ];
    }
}
