<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenance_system_logs', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Quan hệ
            |--------------------------------------------------------------------------
            */

            $table->foreignId('maintenance_system_id')
                ->constrained('maintenance_systems')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Thông tin thay đổi
            |--------------------------------------------------------------------------
            */

            // CREATE, UPDATE, CHANGE_STATUS, CHANGE_TECHNICIAN...
            $table->string('action', 50);

            $table->string('old_status')->nullable();

            $table->string('new_status')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Ghi chú
            |--------------------------------------------------------------------------
            */

            $table->text('note')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Người thực hiện
            |--------------------------------------------------------------------------
            */

            $table->string('performed_by');

            /*
            |--------------------------------------------------------------------------
            | Time
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Index
            |--------------------------------------------------------------------------
            */

            $table->index('maintenance_system_id');
            $table->index('action');
            $table->index('performed_by');
            $table->index(['maintenance_system_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_system_logs');
    }
};