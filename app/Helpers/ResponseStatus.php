<?php

declare(strict_types=1);

namespace App\Helpers;

enum ResponseStatus: string
{
    case SUCCESS = 'success';
    case FAIL = 'fail';
}
