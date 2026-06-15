<?php

namespace Database\Factories;

use App\Models\BotMessage;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BotMessage>
 */
class BotMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'channel_id' => fn () => Channel::create([
                'name' => fake()->unique()->word(),
                'slug' => fake()->unique()->slug(),
                'is_active' => true,
            ])->id,
            'channel_user' => (string) fake()->numberBetween(100000, 999999),
            'user_name' => fake()->name(),
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'response_ms' => fake()->numberBetween(200, 5000),
            'was_helpful' => null,
            'is_escalated' => false,
        ];
    }

    public function escalated(): static
    {
        return $this->state(['is_escalated' => true]);
    }
}
