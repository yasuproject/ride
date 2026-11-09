<?php

namespace Database\Seeders;

use App\Models\Ride;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@mkride.local'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        $samples = [
            ['reference' => 'RIDE-1001', 'passenger_name' => 'Abebe Kebede', 'passenger_phone' => '+251911000001', 'pickup_address' => 'Bole Medhanealem', 'dropoff_address' => 'CMC', 'status' => 'completed', 'fare' => 450.00, 'driver_name' => 'Samuel T.', 'requested_at' => now()->subHours(5)],
            ['reference' => 'RIDE-1002', 'passenger_name' => 'Sara Alemu', 'passenger_phone' => '+251911000002', 'pickup_address' => 'Piassa', 'dropoff_address' => 'Mexico', 'status' => 'in_progress', 'fare' => 220.00, 'driver_name' => 'Dawit M.', 'requested_at' => now()->subMinutes(40)],
            ['reference' => 'RIDE-1003', 'passenger_name' => 'Mulugeta Haile', 'passenger_phone' => '+251911000003', 'pickup_address' => 'Kazanchis', 'dropoff_address' => 'Airport', 'status' => 'assigned', 'fare' => 380.00, 'driver_name' => 'Yonas K.', 'requested_at' => now()->subMinutes(15)],
            ['reference' => 'RIDE-1004', 'passenger_name' => 'Hanna Girma', 'passenger_phone' => '+251911000004', 'pickup_address' => 'Sar Bet', 'dropoff_address' => '4 Kilo', 'status' => 'requested', 'fare' => null, 'driver_name' => null, 'requested_at' => now()->subMinutes(3)],
            ['reference' => 'RIDE-1005', 'passenger_name' => 'Liya Tesfaye', 'passenger_phone' => '+251911000005', 'pickup_address' => 'Jemo', 'dropoff_address' => 'Megenagna', 'status' => 'cancelled', 'fare' => 0.00, 'driver_name' => null, 'requested_at' => now()->subDay()],
        ];

        foreach ($samples as $sample) {
            Ride::query()->updateOrCreate(['reference' => $sample['reference']], $sample);
        }
    }
}
