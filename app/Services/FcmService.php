<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FcmService
{
    public function __construct(
        private readonly string $projectId,
        private readonly string $saPath
    ) {}

    public static function make(): self
    {
        return new self(
            config('services.fcm.project_id'),
            base_path(config('services.fcm.sa_path'))
        );
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

            // Create HTTP handler with SSL verification disabled for local dev
            $httpHandler = null;
            if (app()->environment('local')) {
                $httpHandler = function ($request, array $options = []) {
                    $client = new \GuzzleHttp\Client(['verify' => false]);

                    return $client->send($request, $options);
                };
            }

            $creds = new ServiceAccountCredentials($scopes, $this->saPath);
            $token = $creds->fetchAuthToken($httpHandler);

            return $token['access_token'];
        });
    }

    /**
     * @throws ConnectionException
     * @throws RuntimeException
     */
    public function sendToToken(string $token, ?string $title, ?string $body, array $data = []): array
    {
        return $this->send([
            'token' => $token,
            'notification' => $this->buildNotification($title, $body),
            'data' => $this->normalizeData($data),
        ]);
    }

    /**
     * @throws ConnectionException
     * @throws RuntimeException
     */
    public function sendToTopic(string $topic, ?string $title, ?string $body, array $data = []): array
    {
        return $this->send([
            'topic' => $topic,
            'notification' => $this->buildNotification($title, $body),
            'data' => $this->normalizeData($data),
        ]);
    }

    /**
     * Build FCM notification payload.
     */
    private function buildNotification(?string $title, ?string $body): array
    {
        $notification = [];
        if ($title !== null) {
            $notification['title'] = $title;
        }
        if ($body !== null) {
            $notification['body'] = $body;
        }

        return $notification;
    }

    public function sendToTokens(array $tokens, ?string $title, ?string $body, array $data = []): array
    {
        // HTTP v1 supports "token" OR "topic" OR "condition".
        // For multiple tokens, send in a loop (or batch via your queue).
        $results = [];
        foreach ($tokens as $t) {
            $results[] = $this->sendToToken($t, $title, $body, $data);
        }

        return $results;
    }

    /**
     * @throws ConnectionException
     */
    private function send(array $message): array
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $http = Http::withToken($this->accessToken())->acceptJson();

        // Disable SSL verification for local development
        if (app()->environment('local')) {
            $http = $http->withOptions(['verify' => false]);
        }

        $resp = $http->post($url, [
            'message' => $message + [
                // Optional platform overrides
                'android' => [
                    'priority' => 'HIGH',
                ],
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ]);

        if (! $resp->successful()) {
            $errorBody = $resp->json();
            $errorMessage = $errorBody['error']['message'] ?? $resp->body();

            logger()->error('FCM send failed', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);

            throw new RuntimeException("FCM Error ({$resp->status()}): {$errorMessage}");
        }

        return $resp->json() ?? ['status' => $resp->status()];
    }

    private function normalizeData(array $data): array
    {
        // FCM data must be string values
        $out = [];
        foreach ($data as $k => $v) {
            $out[$k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        return $out;
    }
}
