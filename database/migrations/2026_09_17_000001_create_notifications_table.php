<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Laravel's standard database-notifications shape - User already uses
     * the Notifiable trait, this just gives it somewhere to write to. Powers
     * the topbar bell: "you were assigned/scheduled" alerts, per recipient,
     * with real read/unread state (unlike the bell's other feeds, which are
     * computed live from record status and have no per-user read state).
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
