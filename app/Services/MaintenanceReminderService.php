<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\MaintenanceReminderMail; 
class MaintenanceReminderService
{
    public function sendReminder(
        MaintenanceRequestService $item
    ) {

        Mail::to(
            $item->technician_email
        )->send(
            new MaintenanceReminderMail($item)
        );

        $item->increment(
            'reminder_count'
        );

        $item->update([
            'last_reminded_at' => now()
        ]);

    }
}