<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('telegram:webhook:remove')]
#[Description('Remove o webhook do bot do Telegram')]
class TelegramWebhookRemove extends Command
{
    public function handle(): int
    {
        $token = config('services.telegram.bot_token');

        $response = Http::post("https://api.telegram.org/bot{$token}/deleteWebhook");

        $result = $response->json();

        if ($result['ok'] ?? false) {
            $this->info('Webhook removido com sucesso.');

            return Command::SUCCESS;
        }

        $this->error('Falha ao remover webhook: '.($result['description'] ?? 'erro desconhecido'));

        return Command::FAILURE;
    }
}
