<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
/**
 * Defines the user component of the school-management application.
 *
 * This file contains application logic, framework configuration, or schema
 * changes. The method comments describe each operation's purpose and lifecycle.
 */

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
    /**
     * All Laravel mail notifications, including password recovery, use the
     * canonical email stored on the authenticated user account.
     */
    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }

    /**
     * Helper method for User.php; it performs the setEmailAttribute step and returns or updates the data needed by its caller.
     */
    public function setEmailAttribute($value)
    {
        $email = trim((string) $value);
        $this->attributes['email'] = $email === '' ? null : strtolower($email);
    }
}
