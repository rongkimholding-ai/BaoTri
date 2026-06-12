<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->timestamp('pending_at')
                ->nullable()
                ->after('last_reminded_at');
            $table->timestamp('processing_at')
                ->nullable()
                ->after('pending_at');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn([
                'pending_at',
                'processing_at'
            ]);
        });
    }
};