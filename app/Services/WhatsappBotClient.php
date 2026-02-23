<?php

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;

class WhatsappBotClient
{
    public function __construct(private HttpFactory $http) {}

    public function sendMessage(string $phone, string $message): void
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $apiKey = config('services.whatsapp.bot_api_key');

        if (filled($apiKey)) {
            $headers['x-api-key'] = $apiKey;
        }

        $this->http
            ->timeout((int) config('services.whatsapp.timeout', 10))
            ->withHeaders($headers)
            ->post(rtrim((string) config('services.whatsapp.bot_url'), '/') . '/send-message', [
                'number' => $phone,
                'message' => $message,
            ])
            ->throw();
    }
}
