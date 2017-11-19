<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\DeploymentCollection;
use Aircury\Xml\Node;
use Webpatser\Uuid\Uuid;

class WebServersManipulator
{
    public function addDeployment(Node $webServers, DeploymentCollection $deployments): void
    {
        foreach ($deployments->toArray() as $deploymentName => $deployment) {
            $webServer    = $webServers
                ->getNamedChild('component', ['name' => 'WebServers'])
                ->getNamedChild('option', ['name' => 'servers'])
                ->getNamedChild('webServer', ['name' => $deploymentName]);
            $fileTransfer = $webServer->getNamedChild('fileTransfer');

            $webServer['id']  = $webServer['id'] ?? Uuid::generate(4)->string;
            $webServer['url'] = $deployment->getUrl();

            $fileTransfer['host']       = $deployment->getHost();
            $fileTransfer['port']       = $deployment->getPort();
            $fileTransfer['privateKey'] = $deployment->getPrivateKey();
            $fileTransfer['accessType'] = $deployment->getType();
            $fileTransfer['keyPair']    = 'true';

            $fileTransfer
                ->getNamedChild('advancedOptions')
                ->getNamedChild('advancedOptions')['dataProtectionLevel'] = 'Private';

            $fileTransfer->getNamedChild('option', ['name' => 'port'])['value'] = $deployment->getPort();
        }
    }
}
