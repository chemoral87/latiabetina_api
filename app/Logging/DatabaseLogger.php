<?php

namespace App\Logging;

use App\Logging\DatabaseHandler;
use Monolog\Logger;

class DatabaseLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger($config['name'] ?? 'database');
        $logger->pushHandler(new DatabaseHandler());
        return $logger;
    }
}
