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
        Schema::create('maintenance_request_images', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('maintenance_request_id')
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
        Schema::dropIfExists('maintenance_request_images');
    }
};
