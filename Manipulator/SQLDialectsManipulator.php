<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\SQLDialects;
use Aircury\Xml\Node;

class SQLDialectsManipulator
{
    public function addSQLDialects(Node $sqlDialects, SQLDialects $sql): void
    {
        $sqlDialectsMappings = $sqlDialects->getNamedChild('component', ['name' => 'SqlDialectMappings']);

        foreach ($sql->getMappings() as $path => $ideConfigSQLDialect) {
            $dialectPath = $sqlDialectsMappings->getNamedChild(
                'file',
                ['url' => 'PROJECT' === $path ? 'PROJECT' : 'file://$PROJECT_DIR$/' . $path]
            );

            $dialectPath['dialect'] = $ideConfigSQLDialect;
        }
    }
}
