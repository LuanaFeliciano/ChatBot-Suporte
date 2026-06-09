<?php

namespace App\Ai\Agents;

use App\Models\BotMessage;
use App\Models\Channel;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Tools\FileSearch;

#[MaxSteps(10)]
class SupportAgent implements Agent, Conversational, HasProviderOptions, HasTools
{
    use Promptable;

    public const SYSTEM_PROMPT = <<<'PROMPT'
    Você é um assistente de suporte técnico do aplicativo.

    Sua única fonte de informação são:
    1. Os documentos recuperados pelo File Search.
    2. O histórico da conversa atual.

    Regras obrigatórias:
    - Responda APENAS com base nas informações encontradas nos documentos recuperados.
    - Utilize o histórico da conversa para manter contexto e evitar perguntas repetidas.
    - Nunca invente, deduza ou suponha informações que não estejam presentes na documentação.
    - Nunca invente funcionalidades, fluxos, configurações, regras de negócio ou comportamentos do aplicativo.
    - Se a documentação não contiver a resposta, informe claramente que não encontrou essa informação na base de conhecimento.
    - Quando a informação não estiver disponível, oriente o usuário a entrar em contato com o suporte humano e forneça o link de suporte informado pela empresa.
    - Se a pergunta estiver ambígua, solicite apenas as informações mínimas necessárias para localizar a resposta correta.
    - Se apenas parte da resposta estiver documentada, responda somente com o que foi encontrado e deixe claro o que não está documentado.

    Regras de formato:
    - Responda em português brasileiro.
    - Utilize tom cordial, informal e profissional.
    - Máximo de 3 parágrafos curtos.
    - Use bullet points apenas para etapas sequenciais.
    - Seja objetivo e evite explicações desnecessárias.
    - Finalize com uma pergunta de acompanhamento quando fizer sentido (ex.: "Isso resolveu sua dúvida?").

    PROMPT;

    public function __construct(
        private readonly string $canal,
        private readonly string $channelUser,
        private readonly bool $freshSession = false,
    ) {}

    public function instructions(): string
    {
        $supportUrl = config('services.support_ticket_url');

        return self::SYSTEM_PROMPT."\n\nLink de suporte para escalar: {$supportUrl}";
    }

    public function messages(): iterable
    {
        if ($this->freshSession) {
            return [];
        }

        $channelId = Channel::where('slug', $this->canal)->value('id');

        return BotMessage::where('channel_id', $channelId)
            ->where('channel_user', $this->channelUser)
            ->latest()
            ->limit(10)
            ->get()
            ->reverse()
            ->flatMap(fn (BotMessage $msg) => [
                new Message('user', $msg->question),
                new Message('assistant', $msg->answer),
            ])
            ->all();
    }

    public function tools(): iterable
    {
        return [
            new FileSearch(stores: [config('services.openai.vector_store_id')]),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return match ($provider) {
            Lab::OpenAI => ['reasoning' => ['effort' => 'high']],
            default => [],
        };
    }
}
