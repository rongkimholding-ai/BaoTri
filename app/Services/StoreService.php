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

                $files = [
                    resource_path('json/stores.json'),
                    resource_path('json/stores_mn.json'),
                    resource_path('json/stores_cici_mn.json'),
                ];

                foreach ($files as $file) {

                    if (!file_exists($file)) {
                        continue;
                    }

                    $json = file_get_contents($file);

                    $stores = json_decode(
                        $json,
                        true
                    );

                    if (is_array($stores)) {
                        $result = array_merge(
                            $result,
                            $stores
                        );
                    }
                }

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