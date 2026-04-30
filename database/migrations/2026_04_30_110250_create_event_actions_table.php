<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_actions', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('action')->index();
            $table->text('note')->nullable();
            $table->string('notified_person')->nullable();
            $table->string('notification_method')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_actions');
    }
};
