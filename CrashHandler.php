<?php

namespace App\Helpers\FstHelpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CrashHandler
{
    /**
     * Adds a Crash-Log
     *
     * @param mixed $exception
     * @param string $prefix
     * @param string $message
     * @param array $content
     * @return void
     */
    public static function crash($exception, $prefix = 'laravel', $message = '', $content = [])
    {
        $date = date('Ymd-His');
        $filename = $date . '-crash.txt';
        $path = $prefix . '/' . $filename;
        $details = "Date: $date \nReporter: $prefix \nMessage: $message \n";
        $details .= "Additional Content:\n------------------------------------------------- \n\n\n";
        $details .= json_encode($content, JSON_PRETTY_PRINT) . "\n\n\n";
        $details .= "Original Exception:\n------------------------------------------------- \n\n\n";
        // Manage content:
        $jsonContent = "\n\nJson:\n-------------------------------------------------\n".json_encode([
            'original' => $exception,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ], JSON_PRETTY_PRINT);
        // Create crash-dump
        Storage::disk('crashes')->put($path, $details . $exception . $jsonContent);
        if (!empty($message)) {
            Log::error('[' . $prefix . '] ' . $message);
        }
        Log::info('[CrashHandler] Crash-Report abgelegt unter: ' . $path);
    }

    /**
     * Adds a Log-File to the crashes directory.
     *
     * @param string $message
     * @param mixed $content
     * @param string $prefix
     * @return void
     */
    public static function Log($message, $content, $prefix = '')
    {
        Log::debug('[CrashHandler] Log: ' . $message, [$content]);
        $date = date('Ymd-His');
        $filename = $date . '-log.txt';
        $path = $prefix . '/' . $filename;
        $details = "Date: $date \n";
        $details .= "Reporter: $prefix \n";
        $details .= "Message: $message \n";
        $details .= "Original Message:\n------------------------------------------------- \n\n\n";
        // Manage content:
        $jsonContent = "\n\nJson:\n-------------------------------------------------\n";
        $jsonContent .= json_encode($content, JSON_PRETTY_PRINT);
        // Create crash-dump
        Storage::disk('crashes')->put($path, $details . $message . $jsonContent);
    }
}
