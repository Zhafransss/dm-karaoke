<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'username', 'password', 'role', 'active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function perms(): array
    {
        return config("dmk.roles.{$this->role}.perms", []);
    }

    public function hasPerm(string $perm): bool
    {
        return $this->active && in_array($perm, $this->perms(), true);
    }

    public function roleLabel(): string
    {
        return config("dmk.roles.{$this->role}.label", $this->role);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
