<?php

namespace App\Classes;

use App\Enum\IndentLevelEnum;
use App\Enum\TagEnum;
use App\Enum\UIEnum;
use App\Helpers\ConsoleHelper;
use App\Traits\ConsoleUITrait;

class TextLine
{
    use ConsoleUITrait;

    /** @var string */
    private $text;

    /** @var int */
    private $indentLevel;

    /** @var string */
    private $icon;

    /** @var string */
    private $tag;

    /** @var int */
    private $color;

    /** @var int */
    private $format;

    /**
     * @param string|null $text
     * @param int $indentLevel
     * @param string|null $icon
     * @param string|null $tag
     */
    public function __construct(
        string $text = null, int $indentLevel = IndentLevelEnum::MAIN_LINE,
        string $icon = null, string $tag = null
    )
    {
        $this->text = $text;
        $this->indentLevel = $indentLevel;
        $this->icon = $icon;
        $this->tag = $tag;
        // UI
        $this->resetColorFormat();
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string|null $text
     * @return TextLine
     */
    public function setText(?string $text): TextLine
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return int
     */
    public function getIndentLevel(): int
    {
        return $this->indentLevel;
    }

    /**
     * @return int
     */
    public function getIndentLevelTotal(): int
    {
        return $this->indentLevel + ConsoleHelper::$currentIndentLevel;
    }

    /**
     * @param int $indentLevel
     * @return TextLine
     */
    public function setIndentLevel(int $indentLevel): TextLine
    {
        $this->indentLevel = $indentLevel;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string|null $icon
     * @return TextLine
     */
    public function setIcon(?string $icon): TextLine
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @param string|null $tag
     * @return TextLine
     */
    public function setTag(?string $tag): TextLine
    {
        $this->tag = $tag;
        // format
        if ($this->tag === TagEnum::SUCCESS) {
            $this->color = UIEnum::COLOR_GREEN;
        } else if ($this->tag === TagEnum::ERROR) {
            $this->color = UIEnum::COLOR_RED;
        }
        //
        return $this;
    }

    public function setTagMultiple(array $tags): TextLine
    {
        $this->tag = join(' | ', $tags);
        // format
        if (in_array(TagEnum::SUCCESS, $tags)) {
            $this->color = UIEnum::COLOR_GREEN;
        } else if (in_array(TagEnum::ERROR, $tags)) {
            $this->color = UIEnum::COLOR_RED;
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getColor(): int
    {
        return $this->color;
    }

    /**
     * @param int $color
     * @return TextLine
     */
    public function setColor(int $color): TextLine
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return int
     */
    public function getFormat(): int
    {
        return $this->format;
    }

    /**
     * @param int $format
     * @return TextLine
     */
    public function setFormat(int $format): TextLine
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return void
     */
    public function resetColorFormat(): void
    {
        $this->color = UIEnum::COLOR_NO_SET;
        $this->format = UIEnum::FORMAT_NO_SET;
    }


    // === functions ===
    private function toString(bool $excludeIndent = false): string
    {
        return sprintf(
            "%s%s%s%s",
            $excludeIndent ? ''
                : str_repeat(" ", $this->getIndentLevelTotal() * IndentLevelEnum::AMOUNT_SPACES),
            $this->icon ? $this->icon . ' ' : '',
            $this->tag ? sprintf("[%s] ", $this->tag) : '',
            $this->text
        );
    }

    public function print(string $format, ...$values): TextLine
    {
        // set message text
        $this->text = count($values) ? vsprintf($format, $values) : $format;
        // print
        $finalText = sprintf("%s", $this->toString());
        //     case 1: set both color and format
        if ($this->color !== UIEnum::COLOR_NO_SET && $this->format !== UIEnum::FORMAT_NO_SET) {
            echo self::colorFormat($finalText, $this->color, $this->format, true);
            //
            // case 2: set color only
        } else if ($this->color !== UIEnum::COLOR_NO_SET) {
            echo self::color($finalText, $this->color, true);
            //
            // case 3: no set both color and format
        } else {
            echo $finalText . PHP_EOL;
        }
        //
        return $this;
    }

    public function printTitle(string $format, ...$values): TextLine
    {
        // set current indent level
        self::setCurrentIndentLevel(IndentLevelEnum::ITEM_LINE);
        // set message text
        $this->text = count($values) ? vsprintf($format, $values) : $format;
        // print
        echo self::colorFormat(sprintf("=== %s ===\n", $this->toString(true)),
            UIEnum::COLOR_BLUE, UIEnum::FORMAT_BOLD);
        //
        return $this;
    }

    public function printSubTitle(string $format, ...$values): TextLine
    {
        // set message text
        $this->text = count($values) ? vsprintf($format, $values) : $format;
        // print
        echo self::color(sprintf("-- %s --\n", $this->toString(true)), UIEnum::COLOR_BLUE);
        //
        return $this;
    }

    public function printSeparatorLine(): TextLine
    {
        $this->print($this->getIndentLevelTotal() === IndentLevelEnum::MAIN_LINE
            ? str_repeat("=", 3) : str_repeat("-", 3));
        //
        return $this;
    }

    /**
     * @param bool $condition
     * @param string $messageSuccess
     * @param string $messageError
     * @return void
     */
    public function printCondition(bool $condition, string $messageSuccess, string $messageError): TextLine
    {
        $condition ? $this->setTag(TagEnum::SUCCESS)->print($messageSuccess)
            : $this->setTag(TagEnum::ERROR)->print($messageError);
        //
        return $this;
    }


}
