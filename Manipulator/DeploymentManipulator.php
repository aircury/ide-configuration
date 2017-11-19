<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\DeploymentCollection;
use Aircury\Xml\Node;

class DeploymentManipulator
{
    public function addDeployment(Node $deployment, DeploymentCollection $deploymentCollection): void
    {
        foreach ($deploymentCollection->toArray() as $deploymentName => $deploymentConfiguration) {
            $component = $deployment->getNamedChild(
                'component',
                ['name' => 'PublishConfigData', 'serverName' => $deploymentName]
            );

            $serverData = $component->getNamedChild('serverData');

            $mappingPaths = $deploymentConfiguration->getMappings();

            if (!empty($mappingPaths)) {
                $mappings = $serverData
                    ->getNamedChild('paths', ['name' => $deploymentName])
                    ->getNamedChild('serverdata')
                    ->getNamedChild('mappings');

                foreach ($mappingPaths as $localPath => $deploymentPath) {
                    $mapping = $mappings->getNamedChild('mapping', ['local' => $localPath]);

                    $mapping['deploy'] = $deploymentPath;
                    $mapping['web']    = '/';
                }
            }
        }
    }
}
