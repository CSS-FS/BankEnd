<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'storage' => $this->storageCheck(),
        ];

        $healthy = collect($checks)->every(
            fn (array $check): bool => $check['status'] === 'ok'
        );

        return response()->json([
            'status' => $healthy ? 'ok' : 'error',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function databaseCheck(): array
    {
        try {
            $connection = DB::connection();
            $connection->getPdo();
            $connection->select('SELECT 1');

            return [
                'status' => 'ok',
                'connection' => $connection->getName(),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function cacheCheck(): array
    {
        $key = 'health-check:'.uniqid('', true);

        try {
            Cache::put($key, 'ok', now()->addMinute());

            if (Cache::get($key) !== 'ok') {
                throw new RuntimeException('Cache read/write verification failed.');
            }

            Cache::forget($key);

            return [
                'status' => 'ok',
                'store' => config('cache.default'),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'store' => config('cache.default'),
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function storageCheck(): array
    {
        $path = storage_path();

        try {
            $freeBytes = disk_free_space($path);
            $totalBytes = disk_total_space($path);

            if ($freeBytes === false || $totalBytes === false) {
                throw new RuntimeException('Unable to read disk usage information.');
            }

            $usedBytes = $totalBytes - $freeBytes;

            return [
                'status' => is_writable($path) ? 'ok' : 'error',
                'path' => $path,
                'writable' => is_writable($path),
                'free_bytes' => $freeBytes,
                'used_bytes' => $usedBytes,
                'total_bytes' => $totalBytes,
                'usage_percent' => round(($usedBytes / $totalBytes) * 100, 2),
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'path' => $path,
                'message' => $exception->getMessage(),
            ];
        }
    }
}
