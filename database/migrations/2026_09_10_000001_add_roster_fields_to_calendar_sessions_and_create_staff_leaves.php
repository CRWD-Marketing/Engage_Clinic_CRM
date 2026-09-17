<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Calendar roster redesign: custom activity types, group/custom bookings,
     * recurring bookings, cover shifts, supervision logging, cancel reasons,
     * and a staff_leaves table for the "Mark leave" flow.
     */
    public function up(): void
    {
        Schema::table('calendar_sessions', function (Blueprint $table) {
            // Was an enum - custom types ("Admin time", "Observation", ...) are
            // now allowed, so it becomes a plain string.
            $table->string('activity_type', 50)->change();

            $table->foreignId('cover_for_user_id')->nullable()->after('therapist_id')->constrained('users')->nullOnDelete();
            $table->json('patient_ids')->nullable()->after('patient_name');
            $table->string('activity_label')->nullable()->after('patient_ids');
            $table->json('activity_types')->nullable()->after('activity_type');
            $table->string('cancel_reason', 20)->nullable()->after('status');
            $table->string('recurrence_group', 36)->nullable()->index()->after('notes');
            $table->foreignId('supervised_by')->nullable()->after('recurrence_group')->constrained('users')->nullOnDelete();
            $table->timestamp('supervised_at')->nullable()->after('supervised_by');
            $table->text('supervision_notes')->nullable()->after('supervised_at');
        });

        Schema::create('staff_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('leave_date');
            $table->string('leave_type', 40);
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'leave_date']);
            $table->index('leave_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_leaves');

        Schema::table('calendar_sessions', function (Blueprint $table) {
            $table->dropForeign(['cover_for_user_id']);
            $table->dropForeign(['supervised_by']);
            $table->dropColumn([
                'cover_for_user_id', 'patient_ids', 'activity_label', 'activity_types',
                'cancel_reason', 'recurrence_group', 'supervised_by', 'supervised_at', 'supervision_notes',
            ]);
            $table->enum('activity_type', ['ABA', 'Speech', 'OT', 'Assessment', 'Supervision', 'Parent training'])->change();
        });
    }
};
