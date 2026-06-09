<?php

arch('TelegramAdapter implements ChannelAdapterInterface')
    ->expect('App\Channels\Telegram\TelegramAdapter')
    ->toImplement('App\Channels\Contracts\ChannelAdapterInterface');
