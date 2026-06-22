<?php

namespace App\Mail;

use App\Services\StoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceBuyerMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public $maintenanceRequest;

    public function __construct($maintenanceRequest)
    {
        $this->maintenanceRequest =
            $maintenanceRequest;
    }

    public function envelope(): Envelope
    {
        $cc = [];

        $storeCode =
            $this->maintenanceRequest->branch_code;

        $store =
            app(StoreService::class)
            ->findByCode($storeCode);

        if ($store) {

            if (!empty($store['am_email'])) {
                $cc[] = $store['am_email'];
            }

            if (!empty($store['om_email'])) {
                $cc[] = $store['om_email'];
            }

            if (!empty($store['muasam_email'])) {
                $cc[] = $store['muasam_email'];
            }
        }

        $defaultCc =
            config(
                'mail.notification_cc',
                []
            );

        $cc = array_unique(
            array_merge(
                $defaultCc,
                $cc
            )
        );

        return new Envelope(
            subject: 'Yêu cầu bảo trì cần được mua sắm bổ sung',
            cc: $cc
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-buyer',
            with: [
                'maintenanceRequest' => $this->maintenanceRequest,
            ]
        );
    }
}