<?php

namespace App\Models;

use App\Exceptions\TenantNotResolvedException;
use App\Services\Tenancy\TeamContext;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'team_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, MustVerifyEmail, Notifiable;

    /**
     * Per-request caches to reduce repeat queries.
     *
     * @var array<int, array>
     */
    private array $roleSlugsCache = [];

    /**
     * @var array<int, array>
     */
    private array $permissionCache = [];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    public function platformAdministrator(): HasOne
    {
        return $this->hasOne(PlatformAdministrator::class);
    }

    public function hasRole(string $role): bool
    {
        $teamId = $this->currentTeamId();

        if ($teamId === null) {
            return false;
        }

        if (! isset($this->roleSlugsCache[$teamId])) {
            $this->roleSlugsCache[$teamId] = $this->roles()
                ->where('team_id', $teamId)
                ->pluck('slug')
                ->all();
        }

        return in_array($role, $this->roleSlugsCache[$teamId], true);
    }

    public function hasPermission(string $permission): bool
    {
        $teamId = $this->currentTeamId();

        if ($teamId === null) {
            return false;
        }

        if (! isset($this->permissionCache[$teamId])) {
            $this->permissionCache[$teamId] = $this->roles()
                ->where('team_id', $teamId)
                ->with('permissions')
                ->get()
                ->flatMap(fn ($r) => $r->permissions->pluck('slug'))
                ->unique()
                ->values()
                ->all();
        }

        return in_array($permission, $this->permissionCache[$teamId], true);
    }

    public function isPlatformAdministrator(): bool
    {
        return $this->hasVerifiedEmail()
            && $this->platformAdministrator()->exists();
    }

    private function currentTeamId(): ?int
    {
        try {
            return app(TeamContext::class)->teamId();
        } catch (TenantNotResolvedException) {
            return $this->team_id;
        }
    }

    public function belongsToTeam(Team $team): bool
    {
        return $this->team_id === $team->id || $this->teams()->where('teams.id', $team->id)->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
