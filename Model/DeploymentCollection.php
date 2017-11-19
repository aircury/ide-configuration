<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

class DeploymentCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Deployment::class;
    }

    public function offsetGet($offset): Deployment
    {
        return $this->doOffsetGet($offset);
    }

    /**
     * @return Deployment[]
     */
    public function toArray(): array
    {
        return $this->getElements();
    }

    /**
     * @return Deployment[]
     */
    public function toValuesArray(): array
    {
        return parent::toValuesArray();
    }

    public function first(): Deployment
    {
        return $this->doGetFirst();
    }
}
