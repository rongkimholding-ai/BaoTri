<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceReminderMail extends Mailable
{
    public $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function build()
    {
        return $this
            ->subject('Nhắc việc Yêu cầu bảo trì')
            ->view('emails.maintenance-reminder');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu bảo trì mới',
            cc: config('mail.notification_cc', []),
        );
    }
}
