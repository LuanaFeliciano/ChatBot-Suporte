<?php

use App\Models\Channel;
use Database\Seeders\ChannelSeeder;

it('seeds channels with telegram and whatsapp', function () {
    $this->seed(ChannelSeeder::class);

    expect(Channel::where('slug', 'telegram')->exists())->toBeTrue();
    expect(Channel::where('slug', 'whatsapp')->exists())->toBeTrue();
    expect(Channel::count())->toBe(2);
});

it('running the channel seeder twice does not create duplicate records', function () {
    $this->seed(ChannelSeeder::class);
    $this->seed(ChannelSeeder::class);

    expect(Channel::where('slug', 'telegram')->count())->toBe(1);
    expect(Channel::where('slug', 'whatsapp')->count())->toBe(1);
    expect(Channel::count())->toBe(2);
});
