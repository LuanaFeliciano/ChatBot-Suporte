<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class EscalateConversationTool implements Tool
{
    public const MESSAGE = 'Não consegui confirmar essa informação com segurança no momento. Vou encaminhar essa solicitação para uma análise mais detalhada.';

    public bool $triggered = false;

    public function description(): Stringable|string
    {
        return 'Encaminha a conversa para análise adicional quando a informação não puder ser confirmada com segurança, quando a documentação disponível não for suficiente para responder ou quando a situação exigir acompanhamento posterior.';
    }

    public function handle(Request $request): Stringable|string
    {
        $this->triggered = true;

        return self::MESSAGE;
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
