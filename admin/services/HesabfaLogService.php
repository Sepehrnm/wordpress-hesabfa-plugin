<?php

class HesabfaLogService
{
    public static function writeLogStr($str)
    {
        $fileName = WP_CONTENT_DIR . '/ssbhesabfa-' . date("20y-m-d") . '.txt';
        $str = mb_convert_encoding($str, 'UTF-8');
        file_put_contents($fileName, PHP_EOL . $str, FILE_APPEND);
    }

    public static function writeLogObj($obj)
    {
        $fileName = WP_CONTENT_DIR . '/ssbhesabfa-' . date("20y-m-d") . '.txt';
        ob_start();
        var_dump($obj);
        file_put_contents($fileName, PHP_EOL . ob_get_flush(), FILE_APPEND);
    }

    public static function log($params)
    {
        $fileName = WP_CONTENT_DIR . '/ssbhesabfa-' . date("20y-m-d") . '.txt';
        $log = '';

        foreach ($params as $message) {
            if (is_array($message) || is_object($message)) {
                $log .= date('[r] ') . print_r($message, true) . "\n";
            } elseif (is_bool($message)) {
                $log .= date('[r] ') . ($message ? 'true' : 'false') . "\n";
            } else {
                $log .= date('[r] ') . $message . "\n";
            }
        }

        $log = mb_convert_encoding($log, 'UTF-8');
        file_put_contents($fileName, PHP_EOL . $log, FILE_APPEND);
    }

    public static function readLog($URL)
    {
        return file_exists($URL) ? file_get_contents($URL) : '';
    }

    public static function clearLog()
    {
        $fileName = WP_CONTENT_DIR . '/ssbhesabfa-' . date("20y-m-d") . '.txt';
        if (file_exists($fileName)) {
            file_put_contents($fileName, "");
        }
    }

    public static function getLogFilePath()
    {
        return WP_CONTENT_DIR . '/ssbhesabfa-' . date("20y-m-d") . '.txt';
    }
}