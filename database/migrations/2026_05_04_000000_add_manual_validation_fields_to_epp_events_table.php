<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('epp_events', function (Blueprint $table) {
            $table->string('manual_status')->nullable()->index()->after('human_notification_method');
            $table->timestamp('manual_validated_at')->nullable()->index()->after('manual_status');
            $table->foreignId('manual_validated_by')->nullable()->index()->after('manual_validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('epp_events', function (Blueprint $table) {
            $table->dropColumn([
                'manual_status',
                'manual_validated_at',
                'manual_validated_by',
            ]);
        });
    }
};
