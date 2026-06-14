<?php

namespace App\Filament\Resources\BotMessages\Pages;

use App\Filament\Resources\BotMessages\BotMessageResource;
use Filament\Resources\Pages\ListRecords;

class ListBotMessages extends ListRecords
{
    protected static string $resource = BotMessageResource::class;
}
