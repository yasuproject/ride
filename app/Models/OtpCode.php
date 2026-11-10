<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'phone',
        'code_hash',
        'expires_at',
        'last_sent_at',
        'attempts',
        'max_attempts',
        'verified_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? true;
    }

    public function isConsumed(): bool
    {
        return $this->verified_at !== null;
    }
}
