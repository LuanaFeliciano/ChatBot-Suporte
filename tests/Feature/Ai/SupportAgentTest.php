<?php

use App\Ai\Agents\SupportAgent;
use App\Models\BotMessage;
use App\Models\Channel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Providers\Tools\FileSearch;

it('SupportAgent implements Agent, Conversational, HasTools, HasProviderOptions', function () {
    expect(SupportAgent::class)
        ->toImplement(Agent::class)
        ->toImplement(Conversational::class)
        ->toImplement(HasTools::class)
        ->toImplement(HasProviderOptions::class);
});

it('SupportAgent instructions() includes the support_ticket_url from config', function () {
    config(['services.support_ticket_url' => 'https://meusuporte.exemplo.com']);

    $agent = new SupportAgent('telegram', 'user_123');

    expect($agent->instructions())->toContain('https://meusuporte.exemplo.com');
});

it('SupportAgent tools() returns a FileSearch tool', function () {
    $agent = new SupportAgent('telegram', 'user_123');
    $tools = iterator_to_array($agent->tools());

    expect($tools)->toHaveCount(1)
        ->and($tools[0])->toBeInstanceOf(FileSearch::class);
});

it('SupportAgent model() returns the model from config services.openai.model', function () {
    config(['services.openai.model' => 'gpt-5.5']);

    $agent = new SupportAgent('telegram', 'user_123');

    expect($agent->model())->toBe('gpt-5.5');
});

it('SupportAgent providerOptions() returns reasoning effort high for Lab::OpenAI', function () {
    $agent = new SupportAgent('telegram', 'user_123');

    expect($agent->providerOptions(Lab::OpenAI))
        ->toBe(['reasoning' => ['effort' => 'high']]);
});

it('SupportAgent providerOptions() returns empty array for other providers', function () {
    $agent = new SupportAgent('telegram', 'user_123');

    expect($agent->providerOptions(Lab::Anthropic))->toBe([]);
});

it('SupportAgent messages() returns an empty array when freshSession is true', function () {
    $agent = new SupportAgent('telegram', 'user_123', freshSession: true);

    expect($agent->messages())->toBe([]);
});

it('SupportAgent messages() returns user/assistant message pairs from bot_messages when not fresh', function () {
    $channel = Channel::create(['name' => 'Telegram', 'slug' => 'telegram', 'is_active' => true]);

    BotMessage::factory()->create([
        'channel_id' => $channel->id,
        'channel_user' => 'user_abc',
        'question' => 'Primeira pergunta?',
        'answer' => 'Primeira resposta.',
        'created_at' => now()->subMinutes(2),
    ]);

    BotMessage::factory()->create([
        'channel_id' => $channel->id,
        'channel_user' => 'user_abc',
        'question' => 'Segunda pergunta?',
        'answer' => 'Segunda resposta.',
        'created_at' => now()->subMinute(),
    ]);

    $agent = new SupportAgent('telegram', 'user_abc');
    $messages = $agent->messages();

    expect($messages)->toHaveCount(4)
        ->and($messages[0])->toBeInstanceOf(Message::class)
        ->and($messages[0]->role->value)->toBe('user')
        ->and($messages[0]->content)->toBe('Primeira pergunta?')
        ->and($messages[1]->role->value)->toBe('assistant')
        ->and($messages[1]->content)->toBe('Primeira resposta.')
        ->and($messages[2]->role->value)->toBe('user')
        ->and($messages[2]->content)->toBe('Segunda pergunta?')
        ->and($messages[3]->role->value)->toBe('assistant')
        ->and($messages[3]->content)->toBe('Segunda resposta.');
});

it('SupportAgent messages() ignores messages from other channel_users', function () {
    $channel = Channel::create(['name' => 'Telegram', 'slug' => 'telegram', 'is_active' => true]);

    BotMessage::factory()->create([
        'channel_id' => $channel->id,
        'channel_user' => 'user_abc',
        'question' => 'Minha pergunta?',
        'answer' => 'Minha resposta.',
    ]);

    BotMessage::factory()->create([
        'channel_id' => $channel->id,
        'channel_user' => 'outro_user',
        'question' => 'Pergunta de outro.',
        'answer' => 'Resposta de outro.',
    ]);

    $agent = new SupportAgent('telegram', 'user_abc');
    $messages = $agent->messages();

    expect($messages)->toHaveCount(2);
});

it('SupportAgent can be prompted and returns a string response', function () {
    SupportAgent::fake(['Olá, posso ajudar!']);

    $response = (new SupportAgent('telegram', 'user_123'))->prompt('Como cadastrar?');

    expect((string) $response)->toBe('Olá, posso ajudar!');

    SupportAgent::assertPrompted('Como cadastrar?');
});
