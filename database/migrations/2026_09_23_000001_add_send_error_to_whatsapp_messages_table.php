<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Outbound messages are now written before they are handed to Meta, so the
     * bubble can appear straight away as "Sending". That moves the provider's
     * rejection reason out of the HTTP response — by the time it comes back,
     * the response has already gone — and it has to live on the row instead,
     * or the only thing left to show is a bare "Not sent".
     */
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('send_error')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn('send_error');
        });
    }
};
