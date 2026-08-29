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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            
            // Child information
            $table->string('child_name')->nullable();
            $table->string('child_age')->nullable();
            
            // Parent/Guardian information
            $table->string('parent_guardian_name')->nullable();
            $table->string('phone')->nullable();
            
            // Lead details
            $table->string('source')->nullable(); // Walk-in, Phone call, WhatsApp, Website, Instagram, Referral, Google, Event
            $table->string('interested_in')->nullable(); // ABA therapy, Speech therapy, Occupational therapy, Diagnostic assessment, Early intervention, Combined program
            
            // Insurance & Value
            $table->string('insurance')->nullable(); // Not sure yet, Daman, Daman Enhanced, Thiqa, ADNIC, AXA/GIG, Self-pay
            $table->string('estimated_value')->nullable(); // Est. monthly value in AED
            
            // Notes
            $table->text('notes')->nullable();
            
            // Status tracking
            $table->string('status')->default('new'); // new, contacted, in_progress, converted, lost
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('follow_up_due_at')->nullable();

            // Timestamps
            $table->timestamps();
            
            // Index for faster queries
            $table->index('status');
            $table->index('source');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};