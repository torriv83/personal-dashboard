<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Assistant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyAssistantReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Assistant $assistant,
        public Collection $shifts,
        public int $year,
        public int $month,
        public float $totalMinutes,
        public float $hourlyRate,
        public float $estimatedPay,
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $monthName = Carbon::create($this->year, $this->month, 1)->locale('nb')->translatedFormat('F');

        return new Envelope(
            subject: "Arbeidstidsoversikt for {$monthName} {$this->year}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.monthly-assistant-report',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
