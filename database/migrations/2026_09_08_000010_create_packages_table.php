<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A sellable bundle (service + delivery mode + hours/week + rate) offered
     * when agreeing a client's package during lead intake, e.g. "20 hrs ABA +
     * 10 hr speech, home-based, AED 337/hr".
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('delivery_mode')->nullable(); // e.g. "Home base", "Clinic"
            $table->decimal('hours_per_week', 5, 1)->nullable();
            $table->decimal('rate', 10, 2)->nullable(); // AED per hour
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
