<?php

namespace App\Filament\Resources\BotMessages\Pages;

use App\Filament\Resources\BotMessages\BotMessageResource;
use App\Models\BotMessage;
use App\Models\Channel;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Collection;

class ConversationTimeline extends Page
{
    protected static string $resource = BotMessageResource::class;

    protected string $view = 'filament.resources.bot-messages.pages.conversation-timeline';

    public const int RECENT_WINDOW = 10;

    public Channel|int|string|null $channel = null;

    public string $channelUser;

    /** @var Collection<int, BotMessage> */
    public Collection $messages;

    public function mount(int|string $channel, string $channelUser): void
    {
        $this->channel = Channel::findOrFail($channel);
        $this->channelUser = $channelUser;

        $this->messages = BotMessage::where('channel_id', $this->channel->id)
            ->where('channel_user', $this->channelUser)
            ->oldest()
            ->get();
    }

    public function getTitle(): string
    {
        $userName = $this->messages->last()?->user_name;

        return filled($userName)
            ? "{$userName} ({$this->channelUser})"
            : $this->channelUser;
    }

    public function hasOlderHistory(): bool
    {
        return $this->messages->count() > self::RECENT_WINDOW;
    }

    public function getRecentWindowStartIndex(): int
    {
        return max(0, $this->messages->count() - self::RECENT_WINDOW);
    }

    public function getMaxContentWidth(): Width
    {
        return Width::FourExtraLarge;
    }
}
