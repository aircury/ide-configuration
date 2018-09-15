<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

/**
 * @method void                  offsetSet($offset, Interpreter $element)
 * @method Interpreter           offsetGet($offset)
 * @method Interpreter[]         toArray()
 * @method Interpreter[]         toValuesArray()
 * @method Interpreter|null      first()
 * @method Interpreter|null      last()
 * @method bool                  removeElement(Interpreter $element)
 * @method InterpreterCollection filter(callable $filter, bool $returnNewCollection = true)
 * @method Interpreter|null      pop()
 */
class InterpreterCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Interpreter::class;
    }
}
