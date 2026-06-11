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
        Schema::create('maintenance_acceptances', function (Blueprint $table) {
            $table->id();
        
            $table->foreignId('maintenance_request_id')
                ->constrained()
                ->cascadeOnDelete();
        
            $table->string('result');
            // accepted
            // rejected
        
            $table->text('note')->nullable();
        
            $table->string('confirmed_by')->nullable();
        
            $table->timestamp('confirmed_at')->nullable();
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_acceptances');
    }
};
