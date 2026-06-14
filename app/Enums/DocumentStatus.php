<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DocumentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Uploading = 'uploading';
    case Indexed = 'indexed';
    case Error = 'error';

    public function getLabel(): string
    {
        return __('enums.document_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Indexed => 'success',
            self::Error => 'danger',
            self::Pending, self::Uploading => 'gray',
        };
    }
}
