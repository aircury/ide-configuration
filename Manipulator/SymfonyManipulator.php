<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\Symfony;
use Aircury\Xml\Node;

class SymfonyManipulator
{
    public function addSymfony(Node $symfony, Symfony $symfonyConfiguration): void
    {
        $symfonyPlugin = $symfony->getNamedChild('component', ['name' => 'Symfony2PluginSettings']);

        $symfonyPlugin->getNamedChild('option', ['name' => 'pluginEnabled'])['value'] = 'true';

        if (null !== ($web = $symfonyConfiguration->getWeb())) {
            $symfonyPlugin->getNamedChild('option', ['name' => 'directoryToWeb'])['value'] = $web;
        }

        if (null !== ($app = $symfonyConfiguration->getApp())) {
            $symfonyPlugin->getNamedChild('option', ['name' => 'directoryToApp'])['value'] = $app;
        }
    }
}
