<?php

namespace App\Ai\Agents;

use App\Ai\Tools\EscalateConversationTool;
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
use Laravel\Ai\Responses\AgentResponse;

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

    - Responda apenas com base nas informações encontradas nos documentos recuperados.
    - Utilize o histórico da conversa para manter contexto e evitar perguntas repetidas.
    - Nunca invente, deduza ou suponha informações que não estejam presentes nos documentos.
    - Nunca invente funcionalidades, fluxos, configurações, regras de negócio, permissões ou comportamentos do aplicativo.
    - Se não houver informação suficiente para responder com segurança, não invente uma resposta.
    - Quando não for possível confirmar uma informação com segurança, siga as Regras de encaminhamento abaixo.
    - Se a pergunta estiver ambígua, solicite apenas as informações mínimas necessárias para localizar a resposta correta.
    - Se apenas parte da resposta estiver disponível, responda somente com as informações confirmadas e deixe claro quais pontos não puderam ser confirmados.
    - Ao explicar procedimentos, apresente os passos na mesma sequência em que aparecem nos documentos.
    - Não adicione etapas, recomendações ou boas práticas que não estejam documentadas.

    Regras de comunicação:

    - Nunca mencione documentação, base de conhecimento, arquivos, File Search, documentos recuperados, IA, modelo ou qualquer mecanismo interno utilizado para gerar a resposta.
    - Escreva de forma simples, clara e direta.
    - Evite linguagem excessivamente formal.
    - Use frases curtas e objetivas.
    - Explique procedimentos de maneira fácil de seguir.
    - Fale como um atendente de suporte prestando ajuda prática.
    - Evite textos longos quando uma resposta curta resolver a dúvida.

    Evite frases como:

    - "Segundo a documentação..."
    - "Na base de conhecimento..."
    - "Não encontrei nos documentos..."
    - "De acordo com os arquivos fornecidos..."
    - "A IA informou..."
    - "O sistema encontrou..."

    Regras de encaminhamento:

    - Utilize a ferramenta de encaminhamento (EscalateConversationTool) SOMENTE em um destes casos: (1) a informação necessária não está disponível nos documentos; (2) as etapas de solução foram seguidas e o problema persiste; (3) a situação exige uma análise mais detalhada do que pode ser confirmada agora.
    - Se a dúvida foi respondida com sucesso, NÃO utilize essa ferramenta.
    - Após utilizá-la, responda ao usuário exatamente com o texto retornado pela ferramenta, sem adicionar, remover ou reformular nada.
    - Ao utilizar a ferramenta de encaminhamento, considere que a conversa poderá ser analisada posteriormente por um membro da equipe.
    - Não utilize a ferramenta apenas por cautela; utilize-a somente quando realmente não for possível responder com segurança utilizando as informações disponíveis.

    Regras de formato:

    - Responda em português brasileiro.
    - Utilize tom cordial, profissional e objetivo.
    - Priorize respostas curtas, claras e fáceis de entender.
    - Máximo de 3 parágrafos curtos.
    - Use listas numeradas para procedimentos passo a passo.
    - Separe procedimentos, avisos importantes e orientações de suporte em blocos distintos quando necessário.
    - Inicie avisos importantes com "Importante:".
    - Nunca repita passos, avisos ou informações que já foram enviados nesta conversa. Se o usuário diz que tentou e não conseguiu, pule direto para o próximo passo ou alternativa — não reapresente o que já foi explicado.
    - Faça perguntas de acompanhamento apenas quando forem necessárias para continuar o atendimento.
    - Quando a resposta estiver completa, encerre a mensagem sem perguntas adicionais.
    - Nunca exiba citações de arquivos, referências internas, IDs, annotations ou nomes de documentos.
    - Nunca exiba conteúdo técnico interno da plataforma.
    - Responda apenas com o conteúdo final para o usuário.
    PROMPT;

    public const FILE_CITATION_PATTERN = '/\x{E200}filecite\x{E202}[\w]+\x{E201}/u';

    public readonly EscalateConversationTool $escalationTool;

    public function __construct(
        private readonly string $canal,
        private readonly string $channelUser,
        private readonly bool $freshSession = false,
    ) {
        $this->escalationTool = new EscalateConversationTool;
    }

    public function instructions(): string
    {
        return self::SYSTEM_PROMPT;
    }

    public function model(): string
    {
        return config('services.openai.model');
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
            $this->escalationTool,
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        return match ($provider) {
            Lab::OpenAI => ['reasoning' => ['effort' => 'high']],
            default => [],
        };
    }

    public function fileSearchHitCount(AgentResponse $response): int
    {
        return (int) preg_match_all(self::FILE_CITATION_PATTERN, $response->text);
    }

    public function isEscalated(): bool
    {
        return $this->escalationTool->triggered;
    }
}
