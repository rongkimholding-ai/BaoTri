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
        // Lấy am_email, om_email từ stores.json dựa trên thông tin request (branch_code, branch_name, ...)
        $cc = [];
        $storeCode = $this->request['branch_code'] ?? null;

        if ($storeCode) {
            $storesFiles = [
                resource_path('json/stores.json'),
                resource_path('json/stores_mn.json')
            ];

            foreach ($storesFiles as $storesPath) {
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
                            break 2; // Đã tìm thấy, dừng cả 2 vòng lặp
                        }
                    }
                }
            }
        }

        return new Envelope(
            subject: 'Yêu cầu bảo trì mới',
            cc: config('mail.notification_cc', $cc),
        );
    }
}
