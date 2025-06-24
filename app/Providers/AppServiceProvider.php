<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Events\QueryExecuted;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Log database connection events
        $this->logDatabaseConnections();

        // Log slow queries (optional)
        $this->logSlowQueries();
    }

    /**
     * Log database connection events
     */
    private function logDatabaseConnections(): void
    {
        // Log when database connection is established
        DB::listen(function ($query) {
            // Only log connection info on first query
            static $connectionLogged = [];

            $connectionName = $query->connectionName;

            if (!isset($connectionLogged[$connectionName])) {
                $connection = DB::connection($connectionName);
                $config = $connection->getConfig();

                $this->logDatabaseMessage('✅ Database connection established', [
                    'connection' => $connectionName,
                    'driver' => $config['driver'] ?? 'unknown',
                    'host' => $config['host'] ?? 'unknown',
                    'port' => $config['port'] ?? 'unknown',
                    'database' => $config['database'] ?? 'unknown',
                    'username' => $config['username'] ?? 'unknown',
                ]);

                $connectionLogged[$connectionName] = true;
            }
        });

        // Log database connection errors
        try {
            DB::connection()->getPdo();
            $this->logDatabaseMessage('✅ Database connection test successful', [
                'connection' => config('database.default'),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ], 'info');
        } catch (\Exception $e) {
            $this->logDatabaseMessage('❌ Database connection failed', [
                'connection' => config('database.default'),
                'error' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * Log slow queries (queries taking more than 1 second)
     */
    private function logSlowQueries(): void
    {
        DB::listen(function (QueryExecuted $query) {
            if ($query->time > 1000) { // Log queries taking more than 1 second
                $this->logDatabaseMessage('🐌 Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                ], 'warning');
            }
        });
    }

    /**
     * Log database message with formatted output for console
     */
    private function logDatabaseMessage(string $message, array $context = [], string $level = 'info'): void
    {
        // Add timestamp to context
        $context['timestamp'] = now()->toDateTimeString();

        // Log to database channel (both file and console)
        Log::channel('database')->{$level}($message, $context);

        // Additional console formatting for artisan serve
        if (app()->runningInConsole() && !app()->runningUnitTests()) {
            $formattedMessage = $this->formatConsoleMessage($message, $context, $level);
            echo $formattedMessage . PHP_EOL;
        }
    }

    /**
     * Format message for console output
     */
    private function formatConsoleMessage(string $message, array $context, string $level): string
    {
        $timestamp = now()->format('H:i:s');
        $levelColors = [
            'info' => "\033[32m",    // Green
            'warning' => "\033[33m", // Yellow
            'error' => "\033[31m",   // Red
        ];
        $resetColor = "\033[0m";

        $color = $levelColors[$level] ?? "\033[37m"; // Default white

        $formatted = "{$color}[{$timestamp}] DB: {$message}{$resetColor}";

        // Add key context info
        if (isset($context['host']) && isset($context['database'])) {
            $formatted .= " ({$context['host']}/{$context['database']})";
        }

        if (isset($context['time'])) {
            $formatted .= " - Time: {$context['time']}";
        }

        if (isset($context['error'])) {
            $formatted .= " - Error: {$context['error']}";
        }

        return $formatted;
    }
}
