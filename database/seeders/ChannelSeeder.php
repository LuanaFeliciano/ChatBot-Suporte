<?php

namespace Database\Seeders;

use App\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            ['name' => 'Telegram', 'slug' => 'telegram', 'description' => 'Telegram messaging channel'],
            ['name' => 'WhatsApp', 'slug' => 'whatsapp', 'description' => 'WhatsApp messaging channel'],
        ];

        foreach ($channels as $channel) {
            Channel::updateOrCreate(
                ['slug' => $channel['slug']],
                array_merge($channel, ['is_active' => true]),
            );
        }
    }
}
