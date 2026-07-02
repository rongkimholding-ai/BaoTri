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
        $cc = $this->getStoreCC();

        return new Envelope(
            subject: 'Nhắc việc Yêu cầu bảo trì',
            cc: $cc
        );
    }

    private function getStoreCC(): array
    {
        $cc = [];
        $storeCode = $this->maintenanceRequest->branch_code ?? null;

        if (!$storeCode) {
            return $cc;
        }

        $files = [
            resource_path('json/stores.json'),
            resource_path('json/stores_mn.json'),
            resource_path('json/stores_cici_mn.json')
        ];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $stores = json_decode(file_get_contents($file), true);

            foreach ($stores as $store) {
                if (($store['code'] ?? null) === $storeCode) {

                    if (!empty($store['am_email'])) {
                        $cc[] = $store['am_email'];
                    }

                    if (!empty($store['om_email'])) {
                        $cc[] = $store['om_email'];
                    }

                    break 2;
                }
            }
        }

        return $cc;
    }
}