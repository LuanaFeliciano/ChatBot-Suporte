<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Uploading = 'uploading';
    case Indexed = 'indexed';
    case Error = 'error';
}
