<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // rename column
        if ($this->columnExists('maintenance_requests', 'technician_mail')) {
            DB::statement("
                ALTER TABLE maintenance_requests
                CHANGE technician_mail technician_mobile VARCHAR(255) NULL
            ");
        }

        // change datetime → timestamp (Maria 5.5 OK nhưng nên explicit)
        DB::statement("
            ALTER TABLE maintenance_requests
            MODIFY request_date TIMESTAMP NULL DEFAULT NULL
        ");

        DB::statement("
            ALTER TABLE maintenance_requests
            MODIFY actual_completion_date TIMESTAMP NULL DEFAULT NULL
        ");
    }

    public function down(): void
    {
        if ($this->columnExists('maintenance_requests', 'technician_mail')) {
            DB::statement("
                ALTER TABLE maintenance_requests
                CHANGE technician_mobile technician_mail VARCHAR(255) NULL
            ");
        }

        DB::statement("
            ALTER TABLE maintenance_requests
            MODIFY request_date DATETIME NULL
        ");

        DB::statement("
            ALTER TABLE maintenance_requests
            MODIFY actual_completion_date DATETIME NULL
        ");
    }

    function columnExists($table, $column)
    {
        return DB::selectOne("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ", [$table, $column]) !== null;
    }
};
