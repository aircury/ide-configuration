<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\IDEConfiguration;
use Aircury\Xml\Node;

class IMLManipulator
{
    public function addIML(Node $iml, IDEConfiguration $configuration, string $moduleName): void
    {
        $module         = $configuration->getModule($moduleName);
        $iml['type']    = 'WEB_MODULE';
        $iml['version'] = 4;
        $component      = $iml->getNamedChild('component', ['name' => 'NewModuleRootManager']);

        $component['inherit-compiler-output'] = 'true';

        $component->getNamedChild('exclude-output');

        $content        = $component->getNamedChild('content');
        $content['url'] = 'file://$MODULE_DIR$';

        foreach ($module->getExcluded() as $excludedFolder) {
            $content->getNamedChild('excludeFolder', ['url' => 'file://$MODULE_DIR$/' . $excludedFolder]);
        }

        // Library root means excluded + PHP include_path. See: https://stackoverflow.com/questions/35654320/how-to-configure-directories-when-using-a-symfony-project-in-phpstorm
        foreach ($module->getLibraries() as $libraryFolder) {
            $content->getNamedChild('excludeFolder', ['url' => 'file://$MODULE_DIR$/' . $libraryFolder]);
        }

        foreach ($module->getSources() as $sourceFolder) {
            if ('.' === $sourceFolder) {
                $sourceFolder = '';
            }

            $content->getNamedChild(
                'sourceFolder',
                ['url' => 'file://$MODULE_DIR$/' . $sourceFolder, 'isTestSource' => 'false']
            );
        }

        foreach ($module->getTests() as $sourceFolder) {
            $content->getNamedChild(
                'sourceFolder',
                ['url' => 'file://$MODULE_DIR$/' . $sourceFolder, 'isTestSource' => 'true']
            );
        }

        $component->getNamedChild('orderEntry', ['type' => 'inheritedJdk']);

        $component->getNamedChild('orderEntry', ['type' => 'sourceFolder'])['forTests'] = 'false';
    }
}
