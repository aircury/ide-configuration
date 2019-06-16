<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\DatabaseCollection;
use Aircury\Xml\Node;
use Webpatser\Uuid\Uuid;

class DataSourcesManipulator
{
    private const DRIVER_REF_NAMES = [
        'mysql' => 'mysql',
        'postgresql' => 'postgresql',
        'sqlite' => 'sqlite.xerial',
    ];

    public function addDatabases(Node $dataSources, DatabaseCollection $databases): void
    {
        $dataSourceManager = $dataSources->getNamedChild('component', ['name' => 'DataSourceManagerImpl']);

        $dataSourceManager['format'] = 'xml';
        $dataSourceManager['multifile-model'] = 'true';

        foreach ($databases->toArray() as $databaseName => $database) {
            $dataSource = $dataSourceManager->getNamedChild('data-source', ['name' => $databaseName]);

            if ($database->isReadOnly()) {
                $dataSource['read-only'] = 'true';
            } else {
                unset($dataSource['read-only']);
            }

            $dataSource['source'] = 'LOCAL';
            $dataSource['uuid'] = $dataSource['uuid'] ?? Uuid::generate(4)->string;

            $database->setId($dataSource['uuid']);

            switch ($database->getDriver()) {
                case 'mysql':
                    $dataSource->getNamedChild('jdbc-driver')->contents = 'com.mysql.jdbc.Driver';
                    break;
                case 'postgresql':
                    $dataSource->getNamedChild('jdbc-driver')->contents = 'org.postgresql.Driver';
                    break;
                case 'sqlite':
                    $dataSource->getNamedChild('jdbc-driver')->contents = 'org.sqlite.JDBC';
                    break;
                default:
                    throw new \RuntimeException('Not implemented: ' . $database->getDriver());
            }

            $dataSource->getNamedChild('synchronize')->contents = 'true';
            $dataSource->getNamedChild('driver-ref')->contents = self::DRIVER_REF_NAMES[$database->getDriver()];

            if (null === $database->getPath()) {
                $dataSource->getNamedChild('jdbc-url')->contents = sprintf(
                    'jdbc:%s://%s:%s/%s',
                    $database->getDriver(),
                    $database->getHost(),
                    $database->getPort(),
                    $database->getDatabase()
                );
            } else {
                $dataSource->getNamedChild('jdbc-url')->contents = sprintf(
                    'jdbc:%s:%s',
                    $database->getDriver(),
                    $database->getPath()
                );
            }
        }
    }
}
