<?php

namespace App\Mail;

use App\Services\StoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public $maintenanceRequest;

    public function __construct($maintenanceRequest)
    {
        $this->maintenanceRequest = $maintenanceRequest;
    }

    public function envelope(): Envelope
    {
        $cc = $this->getCC();

        return new Envelope(
            subject: 'Yêu cầu bảo trì đã được hỗ trợ',
            cc: $cc
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-completed',
            with: [
                'maintenanceRequest' => $this->maintenanceRequest,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
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