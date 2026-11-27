<?php

namespace App\Console\Commands;

use App\Services\B2cConsultantSync;
use Illuminate\Console\Command;

class SyncB2cConsultants extends Command
{
    protected $signature = 'consultants:sync-b2c';

    protected $description = 'Tarik konsultan accept+aktif dari DB B2C (read-only) ke B2B.';

    public function handle(): int
    {
        $count = B2cConsultantSync::sync();

        $this->info(sprintf(
            'B2C consultant sync: created=%d updated=%d deactivated=%d deferred=%d',
            $count['created'], $count['updated'], $count['deactivated'], $count['deferred']
        ));

        return self::SUCCESS;
    }
}
