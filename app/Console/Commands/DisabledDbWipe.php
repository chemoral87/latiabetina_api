<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DisabledDbWipe extends Command
{
    protected $signature = 'db:wipe';

    protected $description = 'Disabled to protect application data';

    public function handle(): int
    {
        $this->error('db:wipe is disabled on this project.');

        return self::FAILURE;
    }
}
