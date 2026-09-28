<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The booking form has always asked for the child's name, but contacts had
     * nowhere to put it, so it was folded into the message as a "Child: ..."
     * line. That left every converted lead showing "N/A" where the child's
     * name belongs. Give it a column of its own and lift the existing ones
     * back out of the message text.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('child_name')->nullable()->after('name');
        });

        DB::table('contacts')
            ->whereNull('child_name')
            ->where('message', 'LIKE', '%Child:%')
            ->select('id', 'message')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    if (! preg_match('/^\s*Child:\s*(.+?)\s*$/mi', (string) $row->message, $m)) {
                        continue;
                    }

                    // Drop the now-redundant line, leaving the booking slot and
                    // any note the family actually wrote.
                    $message = preg_replace('/^\s*Child:\s*.+?\s*(\R|$)/mi', '', (string) $row->message, 1);

                    DB::table('contacts')->where('id', $row->id)->update([
                        'child_name' => mb_substr($m[1], 0, 255),
                        'message' => trim((string) $message) ?: null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('child_name');
        });
    }
};
