<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Idea;

use Aircury\IDEConfiguration\Model\Module;
use Aircury\Xml\Node;
use Aircury\Xml\Xml;
use Symfony\Component\Filesystem\Filesystem;

class IdeaProject
{
    private static $paths = [
        'modules'          => '/modules.xml',
        'workspace'        => '/workspace.xml',
        'encodings'        => '/encodings.xml',
        'deployment'       => '/deployment.xml',
        'webServers'       => '/webServers.xml',
        'php'              => '/php.xml',
        'phpTestFramework' => '/php-test-framework.xml',
        'vcs'              => '/vcs.xml',
        'dataSources'      => '/dataSources.xml',
        'dataSourcesLocal' => '/dataSources.local.xml',
        'sqlDialects'      => '/sqldialects.xml',
        'symfony'          => '/symfony2.xml',
        'webResources'     => '/webResources.xml',
    ];

    /**
     * @var string
     */
    private $ideaPath;

    /**
     * @var Node
     */
    private $openFiles;

    public function __construct(string $ideaPath)
    {
        $this->ideaPath = $ideaPath;
    }

    private function getGeneric(string $name): Node
    {
        $path = $this->ideaPath . self::$paths[$name];

        if (isset($this->openFiles[$path])) {
            return $this->openFiles[$path];
        }

        $xml = file_exists($path)
            ? Xml::parseFile($path)
            : new Node('project', ['version' => 4]);

        $this->openFiles[$path] = $xml;

        return $xml;
    }

    public function getIML(Module $module): Node
    {
        $imlDir = '$PROJECT_DIR$' === $module->getRoot()
            ? '$PROJECT_DIR$/.idea'
            : $module->getRoot();

        $path = str_replace(
            '$PROJECT_DIR$',
            dirname($this->ideaPath),
            sprintf('%s/%s.iml', $imlDir, $module->getName())
        );

        if (isset($this->openFiles[$path])) {
            return $this->openFiles[$path];
        }

        $xml = file_exists($path)
            ? Xml::parseFile($path)
            : new Node('module');

        $this->openFiles[$path] = $xml;

        return $xml;
    }

    public function getModules(): Node
    {
        return $this->getGeneric('modules');
    }

    public function getWorkspace(): Node
    {
        return $this->getGeneric('workspace');
    }

    public function getEncodings(): Node
    {
        return $this->getGeneric('encodings');
    }

    public function getDeployment(): Node
    {
        return $this->getGeneric('deployment');
    }

    public function getWebServers(): Node
    {
        return $this->getGeneric('webServers');
    }

    public function getPHP(): Node
    {
        return $this->getGeneric('php');
    }

    public function getPHPTestFramework(): Node
    {
        return $this->getGeneric('phpTestFramework');
    }

    public function getVCS(): Node
    {
        return $this->getGeneric('vcs');
    }

    public function getDataSources(): Node
    {
        return $this->getGeneric('dataSources');
    }

    public function getDataSourcesLocal(): Node
    {
        return $this->getGeneric('dataSourcesLocal');
    }

    public function getSQLDialects(): Node
    {
        return $this->getGeneric('sqlDialects');
    }

    public function getSymfony(): Node
    {
        return $this->getGeneric('symfony');
    }

    public function getWebResources(): Node
    {
        return $this->getGeneric('webResources');
    }

    public function dump(): void
    {
        $filesystem = new Filesystem();

        foreach ($this->openFiles as $path => $xml) {
            $filesystem->dumpFile($path, Xml::dump($xml));
        }
    }
}
