<?php


class HesabfaLogService
{
    private static $fileName = WP_CONTENT_DIR . '/ssbhesabfa.log';

    public static function writeLogStr($str)
    {
        $str = mb_convert_encoding($str, 'UTF-8');
        file_put_contents(self::$fileName, PHP_EOL . $str, FILE_APPEND);
    }

    public static function writeLogObj($obj)
    {
        ob_start();
        var_dump($obj);
        file_put_contents(self::$fileName, PHP_EOL . ob_get_flush(), FILE_APPEND);
    }

    public static function log($params)
    {
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
        file_put_contents(self::$fileName, PHP_EOL . $log, FILE_APPEND);
    }

    public static function readLog()
    {
        return file_get_contents(self::$fileName);
    }

    public static function clearLog() {
        if (file_exists(self::$fileName))
            file_put_contents(self::$fileName, "");
    }

    public static function getLogFilePath() {
        return self::$fileName;
    }

}