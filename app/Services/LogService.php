<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogService
{
    protected static array $loggers = [];

    protected static function logger(string $module): ?Logger {
        try {
            $month = now()->format('Y-m');
            $date = now()->format('Y-m-d');
            $directory = storage_path("logs/{$month}");
            $key = "{$module}_{$date}";

            if (isset(self::$loggers[$key])) {
                return self::$loggers[$key];
            }
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0775, true);
            }

            $logger = new Logger($module);
            $logger->pushHandler(
                new StreamHandler(
                    "{$directory}/{$module}-{$date}.log",
                    Logger::DEBUG
                )
            );

            self::$loggers[$key] = $logger;
            return $logger;
        } catch (\Throwable $e) {
            \Log::error('Create custom logger failed', [
                'module' => $module,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public static function info(
        string $module,
        string $message,
        array $context = []
    ): void {
        try {
            $logger = self::logger($module);
            if ($logger) {
                $logger->info($message, $context);
            }
        } catch (\Throwable $e) {
            \Log::error('Custom info log failed', [
                'module' => $module,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // public static function warning(
    //     string $module,
    //     string $message,
    //     array $context = []
    // ): void {
    //     self::logger($module)->warning(
    //         $message,
    //         $context
    //     );
    // }
    public static function warning(
        string $module,
        string $message,
        array $context = []
    ): void {
        try {
            $logger = self::logger($module);
            if ($logger) {
                $logger->warning($message, $context);
            }
        } catch (\Throwable $e) {
            \Log::error('Custom warning log failed', [
                'module' => $module,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // public static function error(
    //     string $module,
    //     string $message,
    //     array $context = []
    // ): void {
    //     self::logger($module)->error(
    //         $message,
    //         $context
    //     );
    // }
    public static function error(
        string $module,
        string $message,
        array $context = []
    ): void {
        try {
            $logger = self::logger($module);
            if ($logger) {
                $logger->error($message, $context);
            }
        } catch (\Throwable $e) {
            \Log::error('Custom error log failed', [
                'module' => $module,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // public static function debug(
    //     string $module,
    //     string $message,
    //     array $context = []
    // ): void {
    //     self::logger($module)->debug(
    //         $message,
    //         $context
    //     );
    // }
    public static function debug(
        string $module,
        string $message,
        array $context = []
    ): void {
        try {
            $logger = self::logger($module);
            if ($logger) {
                $logger->debug($message, $context);
            }
        } catch (\Throwable $e) {
            \Log::error('Custom debug log failed', [
                'module' => $module,
                'message' => $e->getMessage(),
            ]);
        }
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

    public static function client(
        string $message,
        array $context = []
    ): void {

        self::info(
            'client',
            $message,
            $context
        );

    }
}