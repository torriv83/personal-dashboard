<?php

declare(strict_types=1);

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
        $email = config('app.admin.email');
        $name = config('app.admin.name');
        $password = config('app.admin.password');

        if (! $email || ! $password) {
            $this->command->error('ADMIN_EMAIL og ADMIN_PASSWORD må være satt i .env');

            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Bruker opprettet/oppdatert: {$email}");
    }
}
