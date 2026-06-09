<?php

namespace App\Jobs;

use App\Channels\Telegram\TelegramAdapter;
use App\Services\ChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ProcessChatMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [5, 15, 30];

    public function __construct(
        private readonly string $canal,
        private readonly string $channelUser,
        private readonly int $generation,
    ) {}

    public function handle(ChatService $chat, TelegramAdapter $adapter): void
    {
        $hash = hash('sha256', $this->channelUser);
        $genKey = "user_gen:{$this->canal}:{$hash}";
        $pendingKey = "user_pending:{$this->canal}:{$hash}";

        // Discard if a newer message arrived during the debounce window
        if ((int) Redis::get($genKey) !== $this->generation) {
            return;
        }

        $lock = Cache::lock("user_lock:{$this->canal}:{$hash}", 90);

        if (! $lock->get()) {
            $this->release(3);

            return;
        }

        try {
            // Re-check after acquiring lock to close the race window
            if ((int) Redis::get($genKey) !== $this->generation) {
                return;
            }

            $messages = [];
            while ($raw = Redis::connection()->client()->lpop($pendingKey)) {
                $messages[] = json_decode($raw, true);
            }

            if (empty($messages)) {
                return;
            }

            $userName = $messages[0]['user_name'] ?? null;
            $question = implode("\n", array_column($messages, 'question'));

            $adapter->sendTypingAction($this->channelUser);

            $answer = $chat->process($this->canal, $this->channelUser, $userName, $question);

            // Re-show typing so the indicator is visible right before the reply arrives
            $adapter->sendTypingAction($this->channelUser);
            $this->humanDelay($answer);

            $adapter->sendReply($this->channelUser, $answer);
        } finally {
            $lock->release();
        }
    }

    // Simulates natural response time proportional to answer length (1.5s – 5s)
    private function humanDelay(string $answer): void
    {
        $seconds = min(max(mb_strlen($answer) / 200, 1.5), 5.0);
        usleep((int) ($seconds * 1_000_000));
    }
}
