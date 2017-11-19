<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

class ModuleCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Module::class;
    }

    public function offsetGet($offset): Module
    {
        return $this->doOffsetGet($offset);
    }

    /**
     * @return Module[]
     */
    public function toArray(): array
    {
        return $this->getElements();
    }

    /**
     * @return Module[]
     */
    public function toValuesArray(): array
    {
        return parent::toValuesArray();
    }

    public function first(): Module
    {
        return $this->doGetFirst();
    }
}
