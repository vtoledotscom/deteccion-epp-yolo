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
        Schema::table('epp_events', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence_id')->unique()->nullable()->after('id');
            $table->index('sequence_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('epp_events', function (Blueprint $table) {
            $table->dropIndex(['sequence_id']);
            $table->dropColumn('sequence_id');
        });
    }
};
