<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

/**
 * @method void             offsetSet($offset, Module $element)
 * @method Module           offsetGet($offset)
 * @method Module[]         toArray()
 * @method Module[]         toValuesArray()
 * @method Module|null      first()
 * @method Module|null      last()
 * @method bool             removeElement(Module $element)
 * @method ModuleCollection filter(callable $filter, bool $returnNewCollection = true)
 * @method Module|null      pop()
 */
class ModuleCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Module::class;
    }
}
