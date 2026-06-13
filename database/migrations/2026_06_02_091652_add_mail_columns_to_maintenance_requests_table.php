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
        Schema::table('maintenance_requests', function ($table) {
            $table->string('technician_mobile')
                ->nullable()
                ->after('technician_name');
            $table->integer('reminder_count')
                ->default(0);
            $table->timestamp('last_reminded_at')
                ->nullable();
            $table->string('created_by')
                ->nullable()
                ->after('created_at');
            $table->string('branch_email')
                ->nullable()
                ->after('branch_name');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            //
        });
    }
};
