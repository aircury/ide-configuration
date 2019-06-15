<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\DatabaseCollection;
use Aircury\Xml\Node;

class DatabaseColorsManipulator
{
    public function addColours(Node $colors, DatabaseCollection $databases): void
    {
        $databaseColors = $colors
            ->getNamedChild('component', ['name' => 'DatabaseColorSettings'])
            ->getNamedChild('colors');

        foreach ($databases->toArray() as $database) {
            if (null !== $color = $database->getColor()) {
                $databaseColors->getNamedChild('entry', ['key' => $database->getId()])['value'] = $color;
            }
        }
    }
}
