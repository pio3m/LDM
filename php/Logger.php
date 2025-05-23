<?php

class Logger
{
    private $logFile;

    public function __construct($logFile = 'log.txt')
    {
        $this->logFile = $logFile;
    }

    public function log($message)
    {
        $currentDate = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$currentDate] $message\n", FILE_APPEND);
    }

    public function getLogs($lines = 100)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        $file = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($file), 0, $lines);
    }
}
