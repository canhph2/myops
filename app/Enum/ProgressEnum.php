<?php

namespace App\Enum;

class ProgressEnum
{
    const START = 'start';
    const FINISH = 'finish';

    const SUPPORT_SUB_COMMANDS = [self::START, self::FINISH];
}
