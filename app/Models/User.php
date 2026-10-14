<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A kuartalimpact.org account, created and kept in sync from Kuartal ID
 * sign-in (App\Http\Controllers\Auth\KuartalIdLoginController). No local
 * passwords. Access comes only from Kuartal ID entitlements:
 * kuartalimpact.admin (admin).
 */
#[Fillable(['name', 'email', 'password', 'kuartal_id', 'entitlements', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'entitlements' => 'array',
        ];
    }

    public function hasEntitlement(string $key): bool
    {
        return in_array($key, $this->entitlements ?? [], true);
    }

    public function isAdmin(): bool
    {
        return $this->hasEntitlement((string) config('kuartalimpact.entitlements.admin'));
    }
}
