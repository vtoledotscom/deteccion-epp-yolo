<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epp_events', function (Blueprint $table) {
            $table->string('human_review_status')->default('pending')->index()->after('status');
            $table->foreignId('human_resolved_by')->nullable()->index()->after('human_review_status');
            $table->timestamp('human_resolved_at')->nullable()->index()->after('human_resolved_by');
            $table->text('human_resolution_note')->nullable()->after('human_resolved_at');
            $table->string('human_notified_person')->nullable()->after('human_resolution_note');
            $table->string('human_notification_method')->nullable()->after('human_notified_person');
        });
    }

    public function down(): void
    {
        Schema::table('epp_events', function (Blueprint $table) {
            $table->dropColumn([
                'human_review_status',
                'human_resolved_by',
                'human_resolved_at',
                'human_resolution_note',
                'human_notified_person',
                'human_notification_method',
            ]);
        });
    }
};
