<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;

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
