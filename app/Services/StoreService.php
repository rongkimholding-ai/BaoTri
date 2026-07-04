<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class StoreService
{
    public function findByCode(?string $storeCode): ?array
    {
        if (!$storeCode) {
            return null;
        }

        $stores = Cache::remember(
            'stores_all',
            now()->addHours(1),
            function () {

                $result = [];

                // Lấy danh sách store từ DB thay vì file JSON
                $result = \App\Models\Store::all()->map(function ($store) {
                    return $store->toArray();
                })->toArray();
        

                return $result;
            }
        );

        foreach ($stores as $store) {

            if (
                isset($store['code']) &&
                $store['code'] == $storeCode
            ) {
                return $store;
            }
        }

        return null;
    }
}