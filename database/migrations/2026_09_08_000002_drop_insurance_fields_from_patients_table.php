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
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'insurance_provider',
                'authorized_sessions_total',
                'authorized_sessions_used',
                'authorization_renews_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('insurance_provider')->nullable();
            $table->unsignedInteger('authorized_sessions_total')->nullable();
            $table->unsignedInteger('authorized_sessions_used')->default(0);
            $table->date('authorization_renews_at')->nullable();
        });
    }
};
