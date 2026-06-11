<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Locale: string implements HasLabel
{
    case PtBr = 'pt_BR';
    case En = 'en';

    public function getLabel(): string
    {
        return match ($this) {
            self::PtBr => 'Português (Brasil)',
            self::En => 'English',
        };
    }
}
