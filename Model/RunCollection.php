<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

/**
 * @method void          offsetSet($offset, Run $element)
 * @method Run           offsetGet($offset)
 * @method Run[]         toArray()
 * @method Run[]         toValuesArray()
 * @method Run|null      first()
 * @method Run|null      last()
 * @method bool          removeElement(Run $element)
 * @method RunCollection filter(callable $filter, bool $returnNewCollection = true)
 * @method Run|null      pop()
 */
class RunCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Run::class;
    }
}
