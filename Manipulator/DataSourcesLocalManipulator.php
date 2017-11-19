<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\DatabaseCollection;
use Aircury\Xml\Node;

class DataSourcesLocalManipulator
{
    public function addDatabases(Node $dataSourcesLocal, DatabaseCollection $databases): void
    {
        $dataSourceStorage = $dataSourcesLocal->getNamedChild('component', ['name' => 'dataSourceStorageLocal']);

        foreach ($databases->toArray() as $databaseName => $database) {
            $localDataSource = $dataSourceStorage->getNamedChild('data-source', ['name' => $databaseName]);

            $localDataSource['uuid'] = $database->getId();

            switch ($database->getDriver()) {
                case 'postgresql':
                    if (!empty($schemas = $database->getSchemas())) {
                        $localDataSource
                            ->getNamedChild('introspection-schemas')
                            ->contents = $database->getDatabase() . ':' . implode(',', $schemas);
                    }

                    break;
            }

            $localDataSource->getNamedChild('secret-storage')->contents = 'master_key';
            $localDataSource->getNamedChild('user-name')->contents      = $database->getUsername();
        }
    }
}
