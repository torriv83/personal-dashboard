<?php

namespace App\Console\Commands;

use App\Models\Prescription;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PrescriptionExpiryAlert;
use Illuminate\Console\Command;

class SendPrescriptionAlerts extends Command
{
    protected $signature = 'notifications:prescription-alerts';

    protected $description = 'Send push-varsler for resepter som snart utloper';

    public function handle(): int
    {
        if (! Setting::get('push_prescription_enabled', false)) {
            $this->info('Resept-varsler er deaktivert.');

            return self::SUCCESS;
        }

        // Check if current hour matches user's preferred notification time
        $preferredTime = Setting::get('push_prescription_time', '09:00');
        $preferredHour = (int) explode(':', $preferredTime)[0];
        $currentHour = now()->hour;

        if ($currentHour !== $preferredHour) {
            $this->info("Venter til kl. {$preferredTime} (na er {$currentHour}:00).");

            return self::SUCCESS;
        }

        $user = User::first();
        if (! $user || ! $user->pushSubscriptions()->exists()) {
            $this->info('Ingen aktive push-abonnementer.');

            return self::SUCCESS;
        }

        $alertDays = [14, 7, 3];
        $sentCount = 0;

        // Alert for upcoming expirations
        foreach ($alertDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $prescriptions = Prescription::whereDate('valid_to', $targetDate)->get();

            foreach ($prescriptions as $prescription) {
                $user->notify(new PrescriptionExpiryAlert($prescription, $days));
                $sentCount++;
                $this->info("Sendt varsel for resept: {$prescription->name} ({$days} dager igjen)");
            }
        }

        // Alert for already expired prescriptions (once daily)
        $expiredPrescriptions = Prescription::whereDate('valid_to', '<', now()->toDateString())->get();

        foreach ($expiredPrescriptions as $prescription) {
            $user->notify(new PrescriptionExpiryAlert($prescription, -1));
            $sentCount++;
            $this->info("Sendt varsel for utgått resept: {$prescription->name}");
        }

        $this->info("Ferdig. Sendte {$sentCount} varsler.");

        return self::SUCCESS;
    }
}
