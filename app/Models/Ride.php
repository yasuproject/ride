<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'passenger_name',
        'passenger_phone',
        'pickup_address',
        'dropoff_address',
        'status',
        'fare',
        'driver_name',
        'requested_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fare' => 'decimal:2',
            'requested_at' => 'datetime',
        ];
    }
}
