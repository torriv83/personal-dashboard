<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'pin',
        'lock_timeout_minutes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

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

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Set the user's PIN (hashed).
     */
    public function setPin(string $pin): void
    {
        $this->pin = Hash::make($pin);
        $this->save();
    }

    /**
     * Verify the given PIN against the stored hash.
     */
    public function verifyPin(string $pin): bool
    {
        if (! $this->pin) {
            return false;
        }

        return Hash::check($pin, $this->pin);
    }

    /**
     * Check if the user has a PIN set.
     */
    public function hasPin(): bool
    {
        return ! empty($this->pin);
    }

    /**
     * Check if lock screen is enabled (has PIN and timeout > 0).
     */
    public function isLockScreenEnabled(): bool
    {
        return $this->hasPin() && $this->lock_timeout_minutes > 0;
    }
}
