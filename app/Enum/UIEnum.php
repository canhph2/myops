<?php

namespace App\Enum;

/**
 * reference https://en.wikipedia.org/wiki/ANSI_escape | SGR (Select Graphic Rendition) parameters | Colors
 */
class UIEnum
{
    // === colors ===
    const COLOR_NO_SET = 99999;
    const COLOR_RED = 31;
    const COLOR_GREEN = 32;
    const COLOR_BLUE = 34;

    // === text format ===
    const FORMAT_NO_SET = 99999;
    const FORMAT_NONE = 0;
    const FORMAT_BOLD = 1;
    const FORMAT_ITALIC = 3;
    const FORMAT_UNDERLINE = 4;
}
