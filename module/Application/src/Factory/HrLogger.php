<?php

namespace Application\Factory;

use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

class HrLogger {

    const LOG_DIR = __DIR__ . "/../../../../data/logs/logs.txt";

    private function __construct() {
        
    }

    public static function getInstance(): Logger {
        static $logger = null;
        if ($logger == null) {
            $writer = new Stream(self::LOG_DIR);
            $logger = new Logger();
            $logger->addWriter($writer);
        }
        return $logger;
    }

}
