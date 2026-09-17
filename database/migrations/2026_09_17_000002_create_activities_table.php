<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cross-module activity log (topbar "Activity" dropdown): calendar
     * reassignments and billing events, alongside the lead notes/assignment
     * changes already tracked in lead_activities. Deliberately flat - one
     * human-readable title per entry - rather than a generic polymorphic
     * audit trail, since every entry here is written by the action that
     * caused it and never needs to be queried back by subject.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('title', 500);
            $table->string('url', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
