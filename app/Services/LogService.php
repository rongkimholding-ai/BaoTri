<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogService
{
    protected static array $loggers = [];

    protected static function logger(string $module): Logger
    {
        if (isset(self::$loggers[$module])) {
            return self::$loggers[$module];
        }

        $month = now()->format('Y-m');
        $date = now()->format('Y-m-d');

        $directory = storage_path("logs/{$month}");

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $logger = new Logger($module);

        $logger->pushHandler(
            new StreamHandler(
                "{$directory}/{$module}-{$date}.log",
                Logger::DEBUG
            )
        );

        self::$loggers[$module] = $logger;

        return $logger;
    }

    public static function info(
        string $module,
        string $message,
        array $context = []
    ): void {
        self::logger($module)->info(
            $message,
            $context
        );
    }

    public static function warning(
        string $module,
        string $message,
        array $context = []
    ): void {
        self::logger($module)->warning(
            $message,
            $context
        );
    }

    public static function error(
        string $module,
        string $message,
        array $context = []
    ): void {
        self::logger($module)->error(
            $message,
            $context
        );
    }

    public static function debug(
        string $module,
        string $message,
        array $context = []
    ): void {
        self::logger($module)->debug(
            $message,
            $context
        );
    }

    public static function maintenance(
        string $message,
        array $context = []
    ): void {

        self::info(
            'maintenance',
            $message,
            $context
        );

    }

    public static function system(
        string $message,
        array $context = []
    ): void {

        self::info(
            'system',
            $message,
            $context
        );

    }

    public static function mail(
        string $message,
        array $context = []
    ): void {

        self::info(
            'mail',
            $message,
            $context
        );

    }

    public static function queue(
        string $message,
        array $context = []
    ): void {

        self::info(
            'queue',
            $message,
            $context
        );

    }
}