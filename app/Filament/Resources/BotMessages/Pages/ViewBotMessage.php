<?php

namespace App\Filament\Resources\BotMessages\Pages;

use App\Filament\Resources\BotMessages\BotMessageResource;
use App\Models\BotMessage;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewBotMessage extends ViewRecord
{
    protected static string $resource = BotMessageResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        /** @var BotMessage $record */
        $record = $this->getRecord();

        return match ($record->was_helpful) {
            true => '👍 '.__('conversations.filters.resolved'),
            false => '👎 '.__('conversations.filters.not_resolved'),
            null => __('conversations.filters.unrated'),
        };
    }
}
