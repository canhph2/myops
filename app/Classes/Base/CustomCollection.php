<?php

namespace App\Classes\Base;

use ArrayIterator;
use Closure;
use IteratorAggregate;

class CustomCollection implements IteratorAggregate
{
    /** @var array */
    private $items;

    /**
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return !$this->count();
    }

    /**
     * set item at position index A
     * @param int $index
     * @param $value
     * @return bool
     */
    public function set(int $index, $value): bool
    {
        if ($index < $this->count()) {
            $this->items[$index] = $value;
            return true;
        }
        return false;
    }

    /**
     * - set item at position index A
     * - support sprintf()
     * @param int $index
     * @param string $format
     * @param mixed ...$values
     * @return bool
     */
    public function setStr(int $index, string $format, ...$values): bool
    {
        if ($index < $this->count()) {
            $this->items[$index] = sprintf($format, ...$values);
            return true;
        }
        return false;
    }

    /**
     * get item at position index A
     * @param int $index
     * @return mixed|null
     */
    public function get(int $index)
    {
        return $this->items[$index] ?? null;
    }

    /**
     * add an item to collection
     * @param $item
     * @return CustomCollection
     */
    public function add($item): CustomCollection
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * support add without sprintf()
     * @param string $format
     * @param ...$values
     * @return $this
     */
    public function addStr(string $format, ...$values): CustomCollection
    {
        $this->items[] = sprintf($format, ...$values);
        return $this;
    }

    /**
     * @param array|CustomCollection $arrOrCustomCollection
     * @return CustomCollection
     */
    public function merge($arrOrCustomCollection): CustomCollection
    {
        $this->items = array_merge($this->items,
            $arrOrCustomCollection instanceof self ? $arrOrCustomCollection->toArr() : $arrOrCustomCollection);
        return $this;
    }

    /**
     * support array of strings
     * @param string $separator
     * @return string
     */
    public function join(string $separator): string
    {
        return join($separator, $this->items);
    }

    public function map(Closure $func): CustomCollection
    {
        $mapItems = [];
        foreach ($this->items as $item) {
            $mapItems[] = $func($item);
        }
        return new CustomCollection($mapItems);
    }

    /**
     * @return array
     */
    public function toArr(): array
    {
        return $this->items;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
