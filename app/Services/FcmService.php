<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FcmService
{
    /**
     * @param string $projectId
     * @param string $saPath
     */
    public function __construct(
        private readonly string $projectId,
        private readonly string $saPath
    ) {}

    /**
     * @return self
     */
    public static function make(): self
    {
        return new self(
            config('services.fcm.project_id'),
            base_path(config('services.fcm.sa_path'))
        );
    }

    /**
     * @return string
     */
    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];

            $creds = new ServiceAccountCredentials($scopes, $this->saPath);
            $token = $creds->fetchAuthToken();

            return $token['access_token'];
        });
    }

    /**
     * @param string $token
     * @param array $notification
     * @param array $data
     * @return array
     * @throws ConnectionException
     */
    public function sendToToken(string $token, array $notification, array $data = []): array
    {
        return $this->send([
            'token' => $token,
            'notification' => $notification,
            'data' => $this->normalizeData($data),
        ]);
    }

    /**
     * @param string $topic
     * @param array $notification
     * @param array $data
     * @return array
     * @throws ConnectionException
     */
    public function sendToTopic(string $topic, array $notification, array $data = []): array
    {
        return $this->send([
            'topic' => $topic,
            'notification' => $notification,
            'data' => $this->normalizeData($data),
        ]);
    }

    /**
     * @param array $tokens
     * @param array $notification
     * @param array $data
     * @return array
     */
    public function sendToTokens(array $tokens, array $notification, array $data = []): array
    {
        // HTTP v1 supports "token" OR "topic" OR "condition".
        // For multiple tokens, send in a loop (or batch via your queue).
        $results = [];
        foreach ($tokens as $t) {
            $results[] = $this->sendToToken($t, $notification, $data);
        }

        return $results;
    }

    /**
     * @param array $message
     * @return array
     * @throws ConnectionException
     */
    private function send(array $message): array
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $resp = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post($url, [
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
            // log for troubleshooting
            logger()->error('FCM send failed', [
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
        }

        return $resp->json() ?? ['status' => $resp->status()];
    }

    /**
     * @param array $data
     * @return array
     */
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
