<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RoleName: string implements HasLabel
{
    case Admin = 'Admin';
    case Support = 'Support';

    public function getLabel(): string
    {
        return __('enums.role_name.'.$this->value);
    }
}
