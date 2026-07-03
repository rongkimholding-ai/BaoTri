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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
        
            $table->string('code')->nullable();
        
            $table->string('name');
        
            $table->string('email')->unique();
        
            // north | south
            $table->enum('area', ['north', 'south'])->index();
        
            $table->string('region')->nullable();
        
            // AM
            $table->string('am_name')->nullable();
            $table->string('am_email')->nullable()->index();
        
            // OM
            $table->string('om_name')->nullable();
            $table->string('om_email')->nullable()->index();
        
            // KTV
            $table->string('technician_name')->nullable();
        
            // Mua sắm
            $table->string('muasam_email')->nullable()->index();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
