<?php

namespace App\Enum;

class IndentLevelEnum
{
    const AMOUNT_SPACES = 2; // per indent

    const INCREASE = 1;
    const DECREASE = -1;

    const MAIN_LINE = 0; // no indent
    const ITEM_LINE = 1; // indent with 4 spaces
    const SUB_ITEM_LINE = 2; // indent with 8 spaces
    const LEVEL_3 = 3; // indent with 12 spaces
    const LEVEL_4 = 4; // indent with 16 spaces
    const LEVEL_5 = 5; // indent with 16 spaces
}
