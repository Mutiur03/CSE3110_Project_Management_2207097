<?php

namespace App\Support;

use App\Events\ProjectNotificationPushed;
use Throwable;

class RealtimeNotifier
{
    /**
     * Push a realtime refresh hint without blocking the HTTP response.
     * Skips entirely when Reverb/Pusher is down so board moves stay snappy.
     */
    public static function notificationPushed(string $userId): void
    {
        if (! static::shouldBroadcast()) {
            return;
        }

        dispatch(static function () use ($userId) {
            try {
                broadcast(new ProjectNotificationPushed($userId));
            } catch (Throwable $exception) {
                report($exception);
            }
        })->afterResponse();
    }

    protected static function shouldBroadcast(): bool
    {
        $connection = config('broadcasting.default');

        if (! is_string($connection) || in_array($connection, ['null', 'log'], true)) {
            return false;
        }

        if ($connection === 'reverb') {
            return static::reverbReachable();
        }

        return true;
    }

    protected static function reverbReachable(): bool
    {
        static $reachable = null;

        if ($reachable !== null) {
            return $reachable;
        }

        $host = (string) (config('broadcasting.connections.reverb.options.host') ?: '127.0.0.1');
        $port = (int) (config('broadcasting.connections.reverb.options.port') ?: 8080);

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, 0.2);

        if ($socket === false) {
            return $reachable = false;
        }

        fclose($socket);

        return $reachable = true;
    }
}
