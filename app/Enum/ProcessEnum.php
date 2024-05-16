<?php

namespace App\Enum;

class ProcessEnum
{
    const START = 'start';
    const FINISH = 'finish';

    const SUPPORT_SUB_COMMANDS = [self::START, self::FINISH];
}
