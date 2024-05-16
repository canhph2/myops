<?php

namespace App\Enum;

class TimeEnum
{
    const BEGIN = 'begin';
    const END = 'end';

    const SUPPORT_SUB_COMMANDS = [self::BEGIN, self::END];
}
