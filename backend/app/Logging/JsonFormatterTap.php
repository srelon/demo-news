<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;

class JsonFormatterTap
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
