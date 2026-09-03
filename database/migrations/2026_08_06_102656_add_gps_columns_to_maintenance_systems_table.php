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
        Schema::table('maintenance_systems', function (Blueprint $table) {
            $table->decimal('complete_latitude', 10, 7)
                ->nullable()
                ->after('work_type');

            $table->decimal('complete_longitude', 10, 7)
                ->nullable()
                ->after('complete_latitude');

            $table->decimal('gps_accuracy', 8, 2)
                ->nullable()
                ->after('complete_longitude');

            $table->timestamp('gps_at')
                ->nullable()
                ->after('gps_accuracy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_systems', function (Blueprint $table) {
            $table->dropColumn([
                'complete_latitude',
                'complete_longitude',
                'gps_accuracy',
                'gps_at',
            ]);
        });
    }
};
