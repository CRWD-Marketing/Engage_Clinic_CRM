<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_application_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        // Carry forward any existing single-field notes as the first log entry,
        // so nothing written before this migration is lost.
        DB::table('job_applications')
            ->whereNotNull('notes')
            ->where('notes', '!=', '')
            ->get(['id', 'notes', 'created_at'])
            ->each(function ($application) {
                DB::table('job_application_notes')->insert([
                    'job_application_id' => $application->id,
                    'user_id' => null,
                    'body' => $application->notes,
                    'created_at' => $application->created_at,
                    'updated_at' => $application->created_at,
                ]);
            });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->text('notes')->nullable();
        });

        Schema::dropIfExists('job_application_notes');
    }
};
