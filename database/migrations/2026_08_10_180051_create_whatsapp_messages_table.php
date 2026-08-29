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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_contact_id')->constrained()->cascadeOnDelete();
            $table->string('wa_message_id')->nullable()->unique(); // Meta's wamid, null for messages that failed to send
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('type')->default('text');
            // Meta's sticker attachment id, e.g. the well-known "like" thumbs-up
            // stickers - kept around for debugging/future sticker-specific handling.
            $table->string('sticker_id')->nullable();
            // CDN url for an attachment we can't turn into text - a story a contact
            // replied to/mentioned us in, or a photo/video/file attachment - so the
            // inbox can show the actual image instead of a bare "[type]" label.
            // text() rather than string(): Facebook/Instagram CDN urls carry long
            // signed query strings that routinely exceed a varchar(255).
            $table->text('media_url')->nullable();
            $table->text('body')->nullable();
            $table->string('status')->nullable(); // sent, delivered, read, failed (outbound) / received (inbound)
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
