<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\Behat;
use Aircury\IDEConfiguration\Model\Interpreter;
use Aircury\IDEConfiguration\Util\ComposerLockHelper;
use Aircury\Xml\Node;
use Symfony\Component\Process\Process;

class PHPTestFrameworkManipulator
{
    public function addBehat(
        Node $phpTestFramework,
        Interpreter $interpreter,
        string $projectRootDir,
        Behat $behatConfiguration
    ): void {
        $configurationBySDK = $phpTestFramework
            ->getNamedChild('component', ['name' => 'PhpTestFrameworkSettings'])
            ->getNamedChild('test_tools')
            ->getNamedChild('tool', ['tool_name' => 'Behat'])
            ->getNamedChild('settings')
            ->getNamedChild('configurations')
            ->getNamedChild('configuration_by_sdk', ['interpreter_id' => $interpreter->getId()]);

        $configurationBySDK['configuration_file_path'] = $projectRootDir . '/' . $behatConfiguration->getConfiguration();
        $configurationBySDK['executable_path'] = $projectRootDir . '/' . $behatConfiguration->getBinPath();
        $composerLockHelper['use_configuration_file'] = 'true';

        $toolsCache = $phpTestFramework
            ->getNamedChild('component', ['name' => 'PhpTestFrameworkVersionCache'])
            ->getNamedChild('tools_cache');

        $composerLockHelper = new ComposerLockHelper($projectRootDir);

        $toolsCache
            ->getNamedChild('tool', ['tool_name' => 'Behat'])
            ->getNamedChild('cache')
            ->getNamedChild('versions')
            ->getNamedChild('info', ['id' => 'interpreter-' . $interpreter->getId()])['version'] = $composerLockHelper
                ->getPackageVersion('behat/behat');
    }

    public function addPHPUnit(Node $phpTestFramework, Interpreter $interpreter, string $projectRootDir): void
    {
        $toolsCache = $phpTestFramework
            ->getNamedChild('component', ['name' => 'PhpTestFrameworkVersionCache'])
            ->getNamedChild('tools_cache');

        $composerLockHelper = new ComposerLockHelper($projectRootDir);

        if ($composerLockHelper->hasPackage('phpunit/phpunit')) {
            $phpUnitVersion = $composerLockHelper->getPackageVersion('phpunit/phpunit');
        } else {
            if ($composerLockHelper->hasPackage('symfony/phpunit-bridge')) {
                $phpUnit = new Process($projectRootDir . '/vendor/symfony/phpunit-bridge/bin/simple-phpunit --version');
            } else {
                // Global PHPUnit
                $phpUnit = new Process('phpunit --version');
            }

            $phpUnit->run();

            if (null === ($phpUnitVersion = explode(' ', $phpUnit->getOutput())[1] ?? null)) {
                return;
            }
        }

        $toolsCache
            ->getNamedChild('tool', ['tool_name' => 'PHPUnit'])
            ->getNamedChild('cache')
            ->getNamedChild('versions')
            ->getNamedChild('info', ['id' => 'interpreter-' . $interpreter->getId()])['version'] = $phpUnitVersion;
    }
}
