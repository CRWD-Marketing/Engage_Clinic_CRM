<?php

namespace App\Console\Commands;

use App\Http\Controllers\Whatsapp\WhatsappController;
use App\Models\WhatsappContact;
use Illuminate\Console\Command;

class RefreshWhatsappProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:refresh-profiles {--force : Re-fetch every contact, not just ones missing a name or avatar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-fetch name + profile picture from Meta for Instagram/Facebook contacts (e.g. after a lookup bug fix, or once a cached avatar URL expires)';

    public function handle(WhatsappController $controller): int
    {
        $query = WhatsappContact::whereIn('channel', ['instagram', 'facebook']);

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('name')->orWhereNull('avatar_url');
            });
        }

        $contacts = $query->get();

        if ($contacts->isEmpty()) {
            $this->info('Nothing to refresh. Pass --force to re-fetch every Instagram/Facebook contact regardless.');

            return self::SUCCESS;
        }

        foreach ($contacts as $contact) {
            $profile = $controller->fetchMessengerProfile($contact->wa_id, $contact->channel);

            if (! $profile) {
                $this->warn("Could not fetch profile for contact #{$contact->id} ({$contact->channel}, wa_id={$contact->wa_id}) - lookup failed.");

                continue;
            }

            $before = $contact->only(['name', 'avatar_url']);
            $contact->update(array_filter($profile, fn ($v) => $v !== null));

            $this->line("Contact #{$contact->id}: name '{$before['name']}' -> '{$contact->name}'");
        }

        return self::SUCCESS;
    }
}
