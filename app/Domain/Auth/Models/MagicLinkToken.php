<?php

namespace App\Domain\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MagicLinkToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Generate a new magic link token.
     *
     * @return array{token: string, model: self}
     */
    public static function generate(string $email): array
    {
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        $model = self::create([
            'email' => $email,
            'token' => $hashedToken,
            'expires_at' => now()->addMinutes(15),
        ]);

        return [
            'token' => $rawToken,
            'model' => $model,
        ];
    }

    /**
     * Check if the token is valid (not expired and not used).
     */
    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    /**
     * Mark the token as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}

