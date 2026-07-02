<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;

class MaintenanceSystemAcceptanceMail extends Mailable
{
    public $maintenanceRequest;

    public function __construct($maintenanceRequest)
    {
        $this->maintenanceRequest = $maintenanceRequest;
    }

    public function build()
    {
        return $this
            ->subject('Yêu cầu bảo trì hạ tầng đã được nghiệm thu')
            ->view('emails.maintenance-system-acceptance');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu bảo trì hạ tầng đã được nghiệm thu',
            cc: $this->getCC()
        );
    }

    private function getCC(): array
    {
        $cc = config('mail.notification_cc', []);
        $storeCode = $this->maintenanceRequest->branch_code ?? null;

        if (!$storeCode) {
            return $cc;
        }

        $files = [
            resource_path('json/stores.json'),
            resource_path('json/stores_mn.json'),
            resource_path('json/stores_cici_mb.json'),
            resource_path('json/stores_cici_mn.json'),
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

        return array_values(array_unique($cc));
    }
}