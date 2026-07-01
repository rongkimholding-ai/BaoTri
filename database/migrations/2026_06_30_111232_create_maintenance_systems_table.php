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
        Schema::create('maintenance_systems', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Thông tin sự cố / Dịch vụ
            |--------------------------------------------------------------------------
            */
            $table->string('issue_code', 50)->nullable()->comment('Mã lỗi');
            $table->string('issue_name')->comment('Tên sự cố / Dịch vụ');
            $table->text('issue_description')->nullable()->comment('Mô tả sự cố');
            $table->longText('solution_description')->nullable()->comment('Mô tả khắc phục');
            
            /*
            |--------------------------------------------------------------------------
            | Chi nhánh (Snapshot từ stores.json)
            |--------------------------------------------------------------------------
            */
            $table->string('branch_code', 20)->nullable()->comment('Mã chi nhánh');
            $table->string('branch_name')->comment('Tên chi nhánh');
            $table->string('branch_email')->nullable()->index()->comment('Email chi nhánh');

            /*
            |--------------------------------------------------------------------------
            | Kỹ thuật viên xử lý (Snapshot)
            |--------------------------------------------------------------------------
            */
            $table->string('technician_name')->nullable()->comment('Tên kỹ thuật viên');
            $table->string('technician_email')->nullable()->index()->comment('Email kỹ thuật viên');
            $table->string('technician_mobile', 30)->nullable()->comment('Số điện thoại kỹ thuật viên');

            /*
            |--------------------------------------------------------------------------
            | SLA và thời gian xử lý
            |--------------------------------------------------------------------------
            */
            $table->dateTime('request_date')->comment('Ngày yêu cầu');
            $table->string('actual_completion_date', 30)->nullable()->comment('Thời gian dự kiến hoàn thành/yêu cầu');
            $table->dateTime('completed_at')->nullable()->comment('Thời gian hoàn thành');
            $table->string('standard_completion_time', 191)->nullable()->comment('SLA chuẩn');
            $table->boolean('include_saturday')->default(false)->comment('Tính cả Thứ 7');
            $table->boolean('include_sunday')->default(false)->comment('Tính cả Chủ Nhật');
            $table->boolean('include_holiday')->default(false)->comment('Tính cả ngày lễ');
            $table->boolean('is_off_worktime')->default(false)->comment('Ngoài giờ làm việc');
            $table->string('actual_duration', 191)->nullable()->comment('Thời gian thực hiện thực tế');
            $table->string('sla_status', 191)->nullable()->comment('Trạng thái SLA');

            /*
            |--------------------------------------------------------------------------
            | Trạng thái, lý do trễ, nhà thầu ngoài
            |--------------------------------------------------------------------------
            */
            $table->string('status', 30)->default('NEW')->comment('Trạng thái xử lý');
            $table->text('delay_reason')->nullable()->comment('Lý do trễ');

            /*
            |--------------------------------------------------------------------------
            | Nghiệm thu
            |--------------------------------------------------------------------------
            */
            $table->string('acceptance_result', 191)->nullable()->comment('Kết quả nghiệm thu');
            $table->text('acceptance_note')->nullable()->comment('Ghi chú nghiệm thu');
            $table->string('acceptance_confirmed_by', 191)->nullable()->comment('Người xác nhận nghiệm thu');
            $table->boolean('is_confirmed')->default(false)->comment('Đã xác nhận nghiệm thu');
            $table->timestamp('confirmed_at')->nullable()->comment('Thời gian xác nhận nghiệm thu');

            /*
            |--------------------------------------------------------------------------
            | Người thao tác (Email)
            |--------------------------------------------------------------------------
            */
            $table->string('created_by')->nullable()->comment('Email người tạo');
            $table->string('updated_by')->nullable()->comment('Email người cập nhật');
            $table->string('completed_by')->nullable()->comment('Email người hoàn thành');

            /*
            |--------------------------------------------------------------------------
            | Reminder / tự động
            |--------------------------------------------------------------------------
            */
            $table->timestamp('pending_at')->nullable()->comment('Thời gian chuyển trạng thái chờ thực hiện');
            $table->timestamp('processing_at')->nullable()->comment('Thời gian bắt đầu xử lý');
            
            /*
            |--------------------------------------------------------------------------
            | Laravel timestamps
            |--------------------------------------------------------------------------
            */
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_systems');
    }
};