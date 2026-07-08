<?php

namespace App\Mail;

use App\Services\StoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceSystemBuyerMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public $maintenanceSystem;

    public function __construct($maintenanceSystem)
    {
        $this->maintenanceSystem =
            $maintenanceSystem;
    }

    public function envelope(): Envelope
    {
        $cc = $this->getCC();

        return new Envelope(
            subject: 'Yêu cầu bảo trì hạ tầng cần được mua sắm bổ sung',
            cc: $cc
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-system-buyer',
            with: [
                'maintenanceSystem' => $this->maintenanceSystem,
            ]
        );
    }
    private function getCC(): array
    {
        $cc = config('mail.notification_cc', []);
        $storeCode = $this->maintenanceSystem->branch_code ?? null;
        $storeEmail = $this->maintenanceSystem->branch_email ?? null;

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
            
            if (!empty($store->muasam_email)) {
                $cc[] = $store->muasam_email;
            }
        }

        return array_values(array_unique($cc));
    }
}