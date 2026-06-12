<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {

            $table->id();
            $table->string('branch_code')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('item_category')->nullable();
            $table->text('issue_description')->nullable();
            $table->string('severity')->nullable();
            // $table->string('issue_category')->nullable();
            $table->string('technician_name')->nullable();
            $table->string('standard_completion_time')->nullable();
            $table->text('solution_description')->nullable();
            $table->dateTime('request_date')->nullable();
            $table->dateTime('actual_completion_date')->nullable();
            $table->string('actual_duration')->nullable();
            $table->string('sla_status')->nullable();
            $table->text('delay_reason')->nullable();
            $table->string('outsourced_provider')->nullable();
            $table->string('acceptance_result')->nullable();
            $table->string('acceptance_confirmed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};