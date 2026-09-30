<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CMS Forms: admin-built forms (event registrations and similar) that
     * clients fill in on a public URL. Separate from the existing Contact Us
     * and Careers forms, which keep their own tables.
     *
     * form -> fields, form -> submissions -> values, and a submission may
     * point at the Lead it was converted into. Submissions are never deleted
     * with their form (restrictOnDelete) - they're the client's own record.
     */
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published
            $table->text('success_message')->nullable();
            $table->boolean('auto_create_lead')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('label');
            $table->string('name', 100);
            $table->string('placeholder')->nullable();
            $table->string('help_text', 500)->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'name']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->restrictOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('status', 20)->default('new'); // new, reviewed, converted, ignored
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'status']);
            $table->index('submitted_at');
        });

        Schema::create('form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained('form_submissions')->cascadeOnDelete();
            // The field may later be edited or removed from the form - the
            // name/label/type below are a snapshot of what the client saw.
            $table->foreignId('form_field_id')->nullable()->constrained('form_fields')->nullOnDelete();
            $table->string('field_name', 100);
            $table->string('field_label');
            $table->string('field_type', 30);
            $table->longText('value')->nullable(); // JSON array for multi-select checkboxes
            $table->string('file_path')->nullable();
            $table->string('file_original_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_mime', 120)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_values');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('form_fields');
        Schema::dropIfExists('forms');
    }
};
