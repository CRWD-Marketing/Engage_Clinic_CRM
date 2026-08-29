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
        Schema::create('knowledge_base_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('content');
            $table->string('status')->default('active'); // active | draft | archived
            // 1 = highest priority, 10 = lowest - used as a ranking tiebreaker, not a hard filter.
            $table->unsignedTinyInteger('priority')->default(5);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'category']);
        });

        // Added separately: fullText() requires InnoDB and isn't supported by every
        // driver (e.g. SQLite in local dev) - guard so `migrate` doesn't hard-fail
        // there. KnowledgeBaseService falls back to LIKE search when this index
        // (or the driver) isn't available.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('knowledge_base_entries', function (Blueprint $table) {
                $table->fullText(['title', 'content']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_entries');
    }
};
