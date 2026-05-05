<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE epp_events
            MODIFY event_type ENUM('violation_started', 'violation_resolved', 'compliance_observed') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE epp_events
            MODIFY event_type ENUM('violation_started', 'violation_resolved') NOT NULL
        ");
    }
};
