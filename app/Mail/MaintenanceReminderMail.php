<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;

class MaintenanceReminderMail extends Mailable
{
    public $maintenanceRequest;

    public function __construct($maintenanceRequest)
    {
        $this->maintenanceRequest = $maintenanceRequest;
    }

    public function build()
    {
        return $this
            ->subject('Nhắc việc Yêu cầu bảo trì')
            ->view('emails.maintenance-reminder');
    }

    public function envelope(): Envelope
    {
        $cc = $this->getCC();

        return new Envelope(
            subject: 'Nhắc việc Yêu cầu bảo trì',
            cc: $cc
        );
    }

    private function getCC(): array
    {
        $cc = config('mail.notification_cc', []);
        $storeCode = $this->maintenanceRequest->branch_code ?? null;
        $storeEmail = $this->maintenanceRequest->branch_email ?? null;

        // Nếu không có code và cũng không có email thì return luôn
        if (!$storeCode && !$storeEmail) {
            return $cc;
        }
   

        // Lấy từ Store DB theo code hoặc name
        $query = \App\Models\Store::query();

        if ($storeCode) {
            $query->where('code', $storeCode);
        } elseif ($storeEmail) {
            $query->where('email', $storeEmail);
        }

        $store = $query->first();
        if ($store) {
            if (!empty($store->am_email)) {
                $cc[] = $store->am_email;
            }

            if (!empty($store->om_email)) {
                $cc[] = $store->om_email;
            }
        }

        return array_values(array_unique($cc));
    }
}