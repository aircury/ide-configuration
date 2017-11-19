<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

class RunCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Run::class;
    }

    public function offsetGet($offset): Run
    {
        return $this->doOffsetGet($offset);
    }

    /**
     * @return Run[]
     */
    public function toArray(): array
    {
        return $this->getElements();
    }

    /**
     * @return Run[]
     */
    public function toValuesArray(): array
    {
        return parent::toValuesArray();
    }

    public function first(): Run
    {
        return $this->doGetFirst();
    }
}
