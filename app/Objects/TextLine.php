<?php

namespace app\Objects;

use app\Enum\IndentLevelEnum;
use app\Enum\TagEnum;

class TextLine
{
    /** @var string */
    private $text;

    /** @var int */
    private $indentLevel;

    /** @var string */
    private $icon;

    /** @var string */
    private $tag;

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
        return $this;
    }

    public function setTagMultiple(array $tags): TextLine
    {
        $this->tag = join(' | ', $tags);
        return $this;
    }

    // === functions ===
    private function toString(): string
    {
        return sprintf(
            "%s%s%s%s",
            str_repeat(" ", $this->indentLevel * IndentLevelEnum::AMOUNT_SPACES),
            $this->icon ? $this->icon . ' ' : '',
            $this->tag ? sprintf("[%s] ", $this->tag) : '',
            $this->text
        );
    }

    public function message(string $format, ...$values): TextLine
    {
        var_dump($format);
        var_dump($values);
        // set message text
        $this->text = vsprintf($format, $values);
        // print
        echo sprintf("%s\n", $this->toString());
        //
        return $this;
    }

    public function messageTitle(string $format, ...$values): TextLine
    {
        // set message text
        $this->text = vsprintf($format, $values);
        // print
        echo sprintf("=== %s ===\n", $this->toString());
        //
        return $this;
    }

    public function messageSubTitle(string $format, ...$values): TextLine
    {
        // set message text
        $this->text = vsprintf($format, $values);
        // print
        echo sprintf("-- %s --\n", $this->toString());
        //
        return $this;
    }

    public function messageSeparate(): TextLine
    {
        $this->message($this->indentLevel === IndentLevelEnum::MAIN_LINE ? '===' : '---');
        //
        return $this;
    }

    /**
     * @param bool $condition
     * @param string $messageSuccess
     * @param string $messageError
     * @return void
     */
    public function messageCondition(bool $condition, string $messageSuccess, string $messageError): TextLine
    {
        $condition ? $this->setTag(TagEnum::SUCCESS)->message($messageSuccess)
            : $this->setTag(TagEnum::ERROR)->message($messageError);
        //
        return $this;
    }


}
