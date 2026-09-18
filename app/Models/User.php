<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;

class User extends Authenticatable
{
    use Notifiable, CanResetPassword;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Keep the login/reset identity canonical for every account.
     * Passwords remain account-specific and are stored only in users.password.
     */
    public function setEmailAttribute($value)
    {
        $email = trim((string) $value);
        $this->attributes['email'] = $email === '' ? null : strtolower($email);
    }
}
