<?php

namespace App\Queries;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSystem;

class SlaDueQuery
{
    public function get()
    {
        return MaintenanceRequest::query()
            ->whereIn('sla_status', [
                config('sla_status.code.COMPLETED'),
                config('sla_status.code.LATED'),
            ])
            ->where(function ($q) {
                $q->where('is_confirmed', 0)
                  ->orWhereNull('is_confirmed');
            })
            ->where(function ($q) {
                // Đối với trường hợp COMPLETED thì phải có actual_completion_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)
                $q->where(function ($qq) {
                    $qq->where('sla_status', config('sla_status.code.COMPLETED'))
                       ->whereNotNull('actual_completion_date')
                       ->whereRaw('actual_completion_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)');
                       // ->whereRaw('actual_completion_date <= DATE_SUB(NOW(), INTERVAL 2 MINUTE)');
                })
                // Trường hợp LATED thì actual_completion_date có thể có hoặc không.
                ->orWhere(function ($qq) {
                    $qq->where('sla_status', config('sla_status.code.LATED'));
                    // Không cần ràng actual_completion_date cho LATED
                });
            })
            ->limit(500)
            ->get();
    }

    public function getSystem()
    {
        return MaintenanceSystem::query()
            ->whereIn('status', [
                config('sla_status.code_ht.COMPLETED'),
                config('sla_status.code_ht.LATED'),
            ])
            ->where(function ($q) {
                $q->where('is_confirmed', 0)
                  ->orWhereNull('is_confirmed');
            })
            ->where(function ($q) {
                // Đối với trường hợp COMPLETED thì phải có actual_completion_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)
                $q->where(function ($qq) {
                    $qq->where('status', config('sla_status.code_ht.COMPLETED'))
                       ->whereNotNull('actual_completion_date')
                       ->whereRaw('actual_completion_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)');
                       // ->whereRaw('actual_completion_date <= DATE_SUB(NOW(), INTERVAL 2 MINUTE)');
                })
                // Trường hợp LATED thì actual_completion_date có thể có hoặc không.
                ->orWhere(function ($qq) {
                    $qq->where('status', config('sla_status.code_ht.LATED'));
                    // Không cần ràng actual_completion_date cho LATED
                });
            })
            ->limit(500)
            ->get();
    }
}