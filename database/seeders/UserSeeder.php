<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Opprett enkeltbruker for personal dashboard.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'tor@trivera.net'],
            [
                'name' => 'Tor',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
