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
        Schema::table('contacts', function (Blueprint $table) {
            // The consultation slot a family picked in the booking widget -
            // previously only flattened into the free-text `message` field,
            // which made it impossible to show/sort on its own in the admin list.
            $table->date('booking_date')->nullable()->after('message');
            $table->string('booking_time')->nullable()->after('booking_date');

            // Kept distinct from `status` so the approve/reject outcome survives
            // once status moves on to `contacted` (after the decision email is sent).
            $table->string('booking_decision')->nullable()->after('booking_time'); // approved, rejected
            $table->dateTime('status_email_sent_at')->nullable()->after('booking_decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['booking_date', 'booking_time', 'booking_decision', 'status_email_sent_at']);
        });
    }
};
