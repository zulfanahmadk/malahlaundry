<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

class CreateFeatureLogger
{
    public function __invoke(array $config): Logger
    {
        return new Logger('features', [new FeatureLogHandler($config)], [new PsrLogMessageProcessor()]);
    }
}
