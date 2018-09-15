<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

/**
 * @method void               offsetSet($offset, Database $element)
 * @method Database           offsetGet($offset)
 * @method Database[]         toArray()
 * @method Database[]         toValuesArray()
 * @method Database|null      first()
 * @method Database|null      last()
 * @method bool               removeElement(Database $element)
 * @method DatabaseCollection filter(callable $filter, bool $returnNewCollection = true)
 * @method Database|null      pop()
 */
class DatabaseCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Database::class;
    }
}
