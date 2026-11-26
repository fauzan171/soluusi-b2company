<?php

namespace App\Console\Commands;

use App\Services\B2cConsultantContact;
use Illuminate\Console\Command;

class RefreshConsultantNotifyEmails extends Command
{
    protected $signature = 'consultants:refresh-notify-emails';

    protected $description = 'Isi consultants.notify_email dari B2C (email notifikasi real).';

    public function handle(): int
    {
        $count = B2cConsultantContact::refresh();
        $this->info("Notify emails refreshed: {$count}");

        return self::SUCCESS;
    }
}
