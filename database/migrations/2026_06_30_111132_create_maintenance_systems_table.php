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
            | Thông tin sự cố
            |--------------------------------------------------------------------------
            */

            $table->string('issue_code', 50)
                ->comment('Mã lỗi');

            $table->string('issue_name')
                ->comment('Tên sự cố / Dịch vụ');

            $table->text('issue_description')
                ->nullable()
                ->comment('Mô tả sự cố');

            $table->longText('solution_description')
                ->nullable()
                ->comment('Mô tả khắc phục');

            /*
            |--------------------------------------------------------------------------
            | Chi nhánh (Snapshot từ stores.json)
            |--------------------------------------------------------------------------
            */

            $table->string('branch_code', 20)
                ->comment('Mã chi nhánh');

            $table->string('branch_name')
                ->comment('Tên chi nhánh');

            $table->string('branch_email')
                ->nullable()
                ->index()
                ->comment('Email chi nhánh');

            /*
            |--------------------------------------------------------------------------
            | Kỹ thuật viên xử lý (Snapshot)
            |--------------------------------------------------------------------------
            */

            $table->string('technician_name')
                ->nullable()
                ->comment('Tên kỹ thuật viên');

            $table->string('technician_email')
                ->nullable()
                ->index()
                ->comment('Email kỹ thuật viên');

            $table->string('technician_mobile', 30)
                ->nullable()
                ->comment('Số điện thoại kỹ thuật viên');

            /*
            |--------------------------------------------------------------------------
            | Thời gian nghiệp vụ
            |--------------------------------------------------------------------------
            */

            $table->dateTime('request_date')
                ->comment('Ngày yêu cầu');

            $table->string('actual_completion_date', 30)
                ->comment('Thời gian yêu cầu hoàn thành');

            $table->dateTime('completed_at')
                ->nullable()
                ->comment('Thời gian hoàn thành');

            /*
            |--------------------------------------------------------------------------
            | Trạng thái
            |--------------------------------------------------------------------------
            */

            $table->string('status', 30)
                ->default('NEW')
                ->comment('Trạng thái xử lý');

            $table->text('delay_reason')
                ->nullable()
                ->comment('Lý do trễ');

            /*
            |--------------------------------------------------------------------------
            | Người thao tác (Email)
            |--------------------------------------------------------------------------
            */

            $table->string('created_by')
                ->nullable()
                ->comment('Email người tạo');

            $table->string('updated_by')
                ->nullable()
                ->comment('Email người cập nhật');

            $table->string('completed_by')
                ->nullable()
                ->comment('Email người hoàn thành');

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