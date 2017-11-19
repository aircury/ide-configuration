<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\Xml\Node;

class EncodingsManipulator
{
    public function addDefaultEncoding(Node $encodings): void
    {
        $encodings
            ->getNamedChild('component', ['name' => 'Encoding'])
            ->getNamedChild('file', ['url' => 'PROJECT'])['charset'] = 'UTF-8';
    }
}
