<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SmartStockReport extends Mailable
{
    use Queueable, SerializesModels;

    public $alertData;
    public $reportDate;

    public function __construct($alertData)
    {
        $this->alertData = $alertData;
        $this->reportDate = now()->format('Y-m-d');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Smart Stock Alert Report - Dies Required - ' . $this->reportDate,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.smart-stock-report',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
