<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DisabledMigrateRefresh extends Command
{
    protected $signature = 'migrate:refresh';

    protected $description = 'Disabled to protect application data';

    public function handle(): int
    {
        $this->error('migrate:refresh is disabled on this project.');

        return self::FAILURE;
    }
}
