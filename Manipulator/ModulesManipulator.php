<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\IDEConfiguration;
use Aircury\Xml\Node;

class ModulesManipulator
{
    public function addModules(Node $modules, IDEConfiguration $configuration): void
    {
        $projectModules = $modules
            ->getNamedChild('component', ['name' => 'ProjectModuleManager'])
            ->getNamedChild('modules');

        foreach ($configuration->getModules()->toArray() as $module) {
            $imlDir = '$PROJECT_DIR$' === $module->getRoot()
                ? '$PROJECT_DIR$/.idea'
                : $module->getRoot();

            $projectModules->getNamedChild(
                'module',
                ['filepath' => sprintf('%s/%s.iml', $imlDir, $module->getName())]
            )['fileurl'] = sprintf('file://%s/%s.iml', $imlDir, $module->getName());
        }
    }
}
