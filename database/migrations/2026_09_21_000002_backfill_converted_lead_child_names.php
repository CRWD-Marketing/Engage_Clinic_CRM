<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Contacts converted before contacts.child_name existed produced leads with
     * no child name - they render as "N/A" on the pipeline card. The previous
     * migration recovered those names onto the contact; carry them across to
     * the leads they already created. Only leads that still have no name of
     * their own are touched, so anything a staff member has since filled in by
     * hand wins.
     */
    public function up(): void
    {
        DB::table('contacts')
            ->join('leads', 'leads.id', '=', 'contacts.converted_lead_id')
            ->whereNotNull('contacts.child_name')
            ->where('contacts.child_name', '!=', '')
            ->where(fn ($q) => $q->whereNull('leads.child_name')->orWhere('leads.child_name', ''))
            ->select('leads.id as lead_id', 'contacts.child_name')
            ->orderBy('leads.id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('leads')->where('id', $row->lead_id)->update(['child_name' => $row->child_name]);
                }
            });
    }

    /**
     * Not reversible: there's no way to tell a backfilled name from one a staff
     * member typed, and blanking the latter would lose real work.
     */
    public function down(): void
    {
        //
    }
};
