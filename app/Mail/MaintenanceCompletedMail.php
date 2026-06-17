<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;

class MaintenanceCompletedMail extends Mailable
{
    public $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function build()
    {
        return $this
            ->subject('Yêu cầu bảo trì đã được hỗ trợ')
            ->view('emails.maintenance-completed');
    }

    public function envelope(): Envelope
    {
        // Lấy am_email, om_email từ stores.json dựa trên thông tin request (branch_code, branch_name, ...)
        $cc = [];
        $storeCode = $this->request['branch_code'] ?? null;

        if ($storeCode) {
            $storesPath = resource_path('json/stores.json');
            if (file_exists($storesPath)) {
                $jsonData = file_get_contents($storesPath);
                $stores = json_decode($jsonData, true);

                foreach ($stores as $store) {
                    if (isset($store['code']) && $store['code'] == $storeCode) {
                        if (!empty($store['am_email'])) {
                            $cc[] = $store['am_email'];
                        }
                        if (!empty($store['om_email'])) {
                            $cc[] = $store['om_email'];
                        }
                        break;
                    }
                }
            }
        }

        return new Envelope(
            subject: 'Yêu cầu bảo trì đã được hỗ trợ',
            cc: config('mail.notification_cc', $cc),
        );
    }
}
