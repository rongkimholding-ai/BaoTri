<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $path = resource_path('json/stores.json');

        if (! File::exists($path)) {
            return;
        }

        $stores = json_decode(File::get($path), true);

        $now = now();

        foreach ($stores as $store) {

            if (empty($store['email'])) {
                continue;
            }

            DB::table('users')->updateOrInsert(
                [
                    'email' => trim($store['email'])
                ],
                [
                    'name' => $store['name'] ?? $store['email'],
                    'password' => Hash::make('12345678'),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $path = storage_path('app/stores.json');

        if (! File::exists($path)) {
            return;
        }

        $stores = json_decode(File::get($path), true);

        $emails = collect($stores)
            ->pluck('email')
            ->filter()
            ->toArray();

        DB::table('users')
            ->whereIn('email', $emails)
            ->delete();
    }
};