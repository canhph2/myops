<?php

namespace App\Classes\Base;

use ArrayIterator;
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
    public function add($item):CustomCollection
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param array|CustomCollection $arrOrCustomCollection
     * @return CustomCollection
     */
    public function merge($arrOrCustomCollection):CustomCollection
    {
        $this->items = array_merge($this->items,
            $arrOrCustomCollection instanceof self ? $arrOrCustomCollection->toArr() : $arrOrCustomCollection);
        return $this;
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
