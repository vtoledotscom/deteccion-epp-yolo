<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Asignar sequence_id a eventos existentes usando query builder
        $events = DB::table('epp_events')
            ->whereNull('sequence_id')
            ->orderBy('created_at')
            ->select('id')
            ->get();

        $sequence = 0;
        foreach ($events as $event) {
            DB::table('epp_events')
                ->where('id', $event->id)
                ->update(['sequence_id' => ++$sequence]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Resetear sequence_id a null para eventos existentes
        DB::table('epp_events')->update(['sequence_id' => null]);
    }
};
