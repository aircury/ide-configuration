<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

/**
 * @method void             offsetSet($offset, Server $element)
 * @method Server           offsetGet($offset)
 * @method Server[]         toArray()
 * @method Server[]         toValuesArray()
 * @method Server|null      first()
 * @method Server|null      last()
 * @method bool             removeElement(Server $element)
 * @method ServerCollection filter(callable $filter, bool $returnNewCollection = true)
 * @method Server|null      pop()
 * @method Server|null      shift()
 */
class ServerCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Server::class;
    }
}
