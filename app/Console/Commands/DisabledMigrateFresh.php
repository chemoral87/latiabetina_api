<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DisabledMigrateFresh extends Command
{
    protected $signature = 'migrate:fresh';

    protected $description = 'Disabled to protect application data';

    public function handle(): int
    {
        $this->error('migrate:fresh is disabled on this project.');

        return self::FAILURE;
    }
}
