<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {url}';
    protected $description = 'Set or check the Telegram webhook URL';

    public function handle(TelegramService $telegram)
    {
        $url = $this->argument('url') . '/telegram/webhook/' . config('services.telegram.webhook_secret');

        $result = $telegram->setWebhook($url);
        $this->info('Set webhook result:');
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        $info = Http::get('https://api.telegram.org/bot' . config('services.telegram.token') . '/getWebhookInfo')->json();
        $this->info('Current webhook info:');
        $this->line(json_encode($info, JSON_PRETTY_PRINT));
    }
}