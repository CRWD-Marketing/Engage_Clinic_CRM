<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Closed" (from Therapists & schedules) is distinct from "cancelled": a
     * closed slot is discontinued and disappears from the calendar entirely,
     * whereas a cancelled occurrence stays visible struck-through. The enum
     * can't grow in place, so status becomes a plain string.
     */
    public function up(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->string('status', 20)->default('scheduled')->change();
        });
    }

    public function down(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled')->change();
        });
    }
};
