<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Finance Flow SOP audit trail: session creation, edits and attendance.
     * No foreign key on the session - the history of a deleted session is
     * exactly the part of the trail that must not disappear with it.
     */
    public function up(): void
    {
        Schema::create('calendar_session_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calendar_session_id')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 30); // created | updated | deleted
            $table->string('summary');
            $table->json('changes')->nullable(); // field => [from, to]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_session_events');
    }
};
