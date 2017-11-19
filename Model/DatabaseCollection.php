<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

class DatabaseCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Database::class;
    }

    public function offsetGet($offset): Database
    {
        return $this->doOffsetGet($offset);
    }

    /**
     * @return Database[]
     */
    public function toArray(): array
    {
        return $this->getElements();
    }

    /**
     * @return Database[]
     */
    public function toValuesArray(): array
    {
        return parent::toValuesArray();
    }

    public function first(): Database
    {
        return $this->doGetFirst();
    }
}
