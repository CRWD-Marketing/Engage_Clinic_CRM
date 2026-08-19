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
        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();

            $table->string('child_name');
            $table->unsignedTinyInteger('child_age')->nullable();
            $table->string('programme')->nullable(); // e.g. "ABA", "Speech", "OT"
            $table->unsignedTinyInteger('hours_per_week')->nullable();

            $table->string('status')->default('waiting'); // waiting | placed | removed
            $table->dateTime('joined_at');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
