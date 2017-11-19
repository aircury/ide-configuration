<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

class ServerCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Server::class;
    }

    public function offsetGet($offset): Server
    {
        return $this->doOffsetGet($offset);
    }

    /**
     * @return Server[]
     */
    public function toArray(): array
    {
        return $this->getElements();
    }

    /**
     * @return Server[]
     */
    public function toValuesArray(): array
    {
        return parent::toValuesArray();
    }

    public function first(): Server
    {
        return $this->doGetFirst();
    }
}
