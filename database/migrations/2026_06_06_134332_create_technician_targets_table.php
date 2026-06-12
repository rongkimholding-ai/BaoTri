<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_targets', function (Blueprint $table) {
            $table->id();
            $table->string('technician_name')->unique();
            $table->string('technician_email');
            $table->integer('store_count')->nullable();
            $table->integer('daily_target')->nullable();
            $table->integer('monthly_target')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_targets');
    }
};