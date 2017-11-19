<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\IDEConfiguration;
use Aircury\Xml\Node;

class WebResourcesManipulator
{
    public function addWebResources(Node $webResources, IDEConfiguration $configuration, string $moduleName): void
    {
        $resourceRoots = $webResources
            ->getNamedChild('component', ['name' => 'WebResourcesPaths'])
            ->getNamedChild('contentEntries')
            ->getNamedChild('entry', ['url' => 'file://$PROJECT_DIR$'])
            ->getNamedChild('entryData')
            ->getNamedChild('resourceRoots');

        foreach ($configuration->getModule($moduleName)->getResources() as $resource) {
            $resourceRoots->getNamedChild('path', ['value' => 'file://$PROJECT_DIR$/' . $resource]);
        }
    }
}
