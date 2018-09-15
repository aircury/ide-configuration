<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Aircury\Collection\AbstractCollection;

/**
 * @method void                 offsetSet($offset, Deployment $element)
 * @method Deployment           offsetGet($offset)
 * @method Deployment[]         toArray()
 * @method Deployment[]         toValuesArray()
 * @method Deployment|null      first()
 * @method Deployment|null      last()
 * @method bool                 removeElement(Deployment $element)
 * @method DeploymentCollection filter(callable $filter, bool $returnNewCollection = true)
 * @method Deployment|null      pop()
 */
class DeploymentCollection extends AbstractCollection
{
    public function getClass(): string
    {
        return Deployment::class;
    }
}
