<?php

namespace App\Domain\Users\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Billing\Traits\HasCredits;
use App\Domain\Billing\Traits\HasSubscription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, Billable, HasCredits, HasSubscription;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'suspended_at',
        'credits_balance',
        'current_plan_key',
        'scheduled_plan_key',
        'scheduled_plan_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
            'suspended_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'credits_balance' => 'integer',
            'scheduled_plan_date' => 'datetime',
        ];
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if the user is suspended.
     */
    public function isSuspended(): bool
    {
        return ! is_null($this->suspended_at);
    }

    /**
     * Check if the user has a pending plan change scheduled.
     */
    public function hasPendingPlanChange(): bool
    {
        return ! is_null($this->scheduled_plan_key) && ! is_null($this->scheduled_plan_date);
    }

    /**
     * Cancel any pending plan changes.
     */
    public function cancelPendingPlanChange(): void
    {
        $this->update([
            'scheduled_plan_key' => null,
            'scheduled_plan_date' => null,
        ]);
    }
}

