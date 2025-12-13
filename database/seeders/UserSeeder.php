<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Opprett enkeltbruker for personal dashboard.
     *
     * Henter verdier fra miljøvariabler (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD).
     */
    public function run(): void
    {
        $email = config('app.admin.email');
        $name = config('app.admin.name');
        $password = config('app.admin.password');

        if (! $email || ! $password) {
            $this->command->error('ADMIN_EMAIL og ADMIN_PASSWORD må være satt i .env');

            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name ?? 'Admin',
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Bruker opprettet/oppdatert: {$email}");
    }
}
