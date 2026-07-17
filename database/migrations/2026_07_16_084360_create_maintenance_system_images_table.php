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
        Schema::create('maintenance_system_images', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('maintenance_system_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->string('path');
            $table->string('uploaded_by')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_system_images');
    }
};
