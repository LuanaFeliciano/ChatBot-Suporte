<?php

use App\Ai\Agents\SupportAgent;
use App\Ai\Tools\EscalateConversationTool;
use App\Models\BotMessage;
use App\Models\Channel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Providers\Tools\FileSearch;
use Laravel\Ai\Responses\Data\ToolCall;

it('SupportAgent implements Agent, Conversational, HasTools, HasProviderOptions', function () {
    expect(SupportAgent::class)
        ->toImplement(Agent::class)
        ->toImplement(Conversational::class)
        ->toImplement(HasTools::class)
        ->toImplement(HasProviderOptions::class);
});

it('SupportAgent tools() returns a FileSearch tool and an EscalateConversationTool', function () {
    $agent = new SupportAgent('telegram', 'user_123');
    $tools = iterator_to_array($agent->tools());

    expect($tools)->toHaveCount(2)
        ->and($tools[0])->toBeInstanceOf(FileSearch::class)
        ->and($tools[1])->toBeInstanceOf(EscalateConversationTool::class);
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

it('SupportAgent isEscalated() returns false when the escalation tool was not called', function () {
    SupportAgent::fake(['Olá, posso ajudar!']);

    $agent = new SupportAgent('telegram', 'user_123');
    $agent->prompt('Como cadastrar?');

    expect($agent->isEscalated())->toBeFalse();
});

it('SupportAgent isEscalated() returns true and returns the escalation message when the escalation tool is called', function () {
    SupportAgent::fake([
        new ToolCall(id: 'call_1', name: 'EscalateConversationTool', arguments: []),
        EscalateConversationTool::MESSAGE,
    ]);

    $agent = new SupportAgent('telegram', 'user_123');
    $response = $agent->prompt('Como cadastrar?');

    expect($agent->isEscalated())->toBeTrue()
        ->and((string) $response)->toBe(EscalateConversationTool::MESSAGE);
});
