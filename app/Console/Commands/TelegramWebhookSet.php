<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('telegram:webhook:set {url : A URL pública do webhook (ex: https://abc.ngrok.io)}')]
#[Description('Registra o webhook do bot do Telegram')]
class TelegramWebhookSet extends Command
{
    public function handle(): int
    {
        $token = config('services.telegram.bot_token');
        $secret = config('services.telegram.webhook_secret');
        $url = $this->argument('url');

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => rtrim($url, '/').'/api/webhook/telegram',
            'secret_token' => $secret,
        ]);

        $result = $response->json();

        if ($result['ok'] ?? false) {
            $this->info('Webhook registrado com sucesso.');
            $this->line('URL: '.rtrim($url, '/').'/api/webhook/telegram');

            return Command::SUCCESS;
        }

        $this->error('Falha ao registrar webhook: '.($result['description'] ?? 'erro desconhecido'));

        return Command::FAILURE;
    }
}
