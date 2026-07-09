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
        Schema::create('mt_update_log_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('mt_update_log_id');

            $table->foreign('mt_update_log_id', 'fk_mt_log_detail')
                ->references('id')
                ->on('mt_update_logs')
                ->cascadeOnDelete();

            // Tên field trong DB
            $table->string('field');

            // Tên hiển thị
            $table->string('field_name');

            // Giá trị cũ
            $table->text('old_value')->nullable();

            // Giá trị mới
            $table->text('new_value')->nullable();

            $table->timestamps();

            $table->index('mt_update_log_id');
            $table->index('field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mt_update_log_details');
    }
};