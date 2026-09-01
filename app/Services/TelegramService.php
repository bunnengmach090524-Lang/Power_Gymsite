<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $token;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('services.telegram.token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    public function sendMessage(string $chatId, string $text): bool
    {
        $response = Http::post("{$this->apiUrl}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        if (! $response->successful()) {
            Log::warning('Telegram sendMessage failed', ['chat_id' => $chatId, 'body' => $response->body()]);
        }

        return $response->successful();
    }

    /**
     * Send a QR code image (raw SVG/PNG bytes) as a photo.
     * Telegram's sendPhoto requires PNG/JPEG — SVG won't render as a photo,
     * so the caller should pass PNG bytes (see QrCode::format('png') in the controller).
     */
    public function sendPhoto(string $chatId, string $photoBinary, string $caption = ''): bool
    {
        $response = Http::attach('photo', $photoBinary, 'trainer-qr.png')
            ->post("{$this->apiUrl}/sendPhoto", [
                'chat_id' => $chatId,
                'caption' => $caption,
            ]);

        if (! $response->successful()) {
            Log::warning('Telegram sendPhoto failed', ['chat_id' => $chatId, 'body' => $response->body()]);
        }

        return $response->successful();
    }

    public function setWebhook(string $url): array
    {
        return Http::post("{$this->apiUrl}/setWebhook", ['url' => $url])->json();
    }
}