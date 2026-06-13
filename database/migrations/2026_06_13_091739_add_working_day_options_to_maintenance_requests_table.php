<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {

            $table->boolean('include_saturday')
                ->default(true)
                ->after('standard_completion_time');

            $table->boolean('include_sunday')
                ->default(false)
                ->after('include_saturday');

            $table->boolean('include_holiday')
                ->default(false)
                ->after('include_sunday');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {

            $table->dropColumn([
                'include_saturday',
                'include_sunday',
                'include_holiday',
            ]);
        });
    }
};