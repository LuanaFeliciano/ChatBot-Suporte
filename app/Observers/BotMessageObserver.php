<?php

namespace App\Observers;

use App\Models\BotMessage;

class BotMessageObserver
{
    public function creating(BotMessage $botMessage): void
    {
        $botMessage->question_normalized = $this->normalize($botMessage->question);
    }

    private function normalize(string $question): string
    {
        $normalized = mb_strtolower($question);
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }
}
