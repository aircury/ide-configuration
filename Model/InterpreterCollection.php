<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

class InterpreterCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Interpreter::class;
    }

    public function offsetGet($offset): Interpreter
    {
        return $this->doOffsetGet($offset);
    }

    /**
     * @return Interpreter[]
     */
    public function toArray(): array
    {
        return $this->getElements();
    }

    /**
     * @return Interpreter[]
     */
    public function toValuesArray(): array
    {
        return parent::toValuesArray();
    }

    public function first(): Interpreter
    {
        return $this->doGetFirst();
    }
}
