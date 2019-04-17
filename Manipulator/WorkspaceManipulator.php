<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\IDEConfiguration;
use Aircury\IDEConfiguration\Model\Composer;
use Aircury\IDEConfiguration\Model\Interpreter;
use Aircury\IDEConfiguration\Model\RunCollection;
use Aircury\Xml\Node;
use Webpatser\Uuid\Uuid;

class WorkspaceManipulator
{
    public function addComposer(Node $workspace, Composer $composer): void
    {
        $composerSettings = $workspace->getNamedChild('component', ['name' => 'ComposerSettings']);

        if (!$composer->getAsk()) {
            $composerSettings['doNotAsk'] = 'true';
        }

        $pharConfigPath = $composerSettings->getNamedChild('pharConfigPath');
        $pharConfigPath->contents = '$PROJECT_DIR$/composer.json';

        if ($composer->getSynchronize()) {
            $composerSettings['synchronizationState'] = 'SYNCHRONIZE';
        }
    }

    public function addServers(Node $workspace, IDEConfiguration $configuration): void
    {
        $servers = $workspace
            ->getNamedChild('component', ['name' => 'PhpServers'])
            ->getNamedChild('servers');

        foreach ($configuration->getServers()->toArray() as $serverName => $serverSettings) {
            $existingServers = $servers->children->filterByAttribute('name', $serverName);

            if ($existingServers->count() >= 2) {
                throw new \LogicException(
                    'Found two servers already configured with the name %s, so cannot configure or overwrite this one',
                    $serverName
                );
            }

            $server = $existingServers->isEmpty()
                ? $servers->addChild(new Node('server', ['name' => $serverName, 'id' => Uuid::generate(4)->string]))
                : $existingServers->first();

            if (null !== ($host = $serverSettings->getHost())) {
                $server['host'] = $host;
            }

            if (!empty($mappings = $serverSettings->getMappings())) {
                $server['use_path_mappings'] = 'true';

                $pathMappings = $server->getNamedChild('path_mappings');

                foreach ($mappings as $localRoot => $remoteRoot) {
                    $pathMappings->getNamedChild('mapping', ['local-root' => $localRoot])['remote-root'] = $remoteRoot;
                }
            }
        }
    }

    public function addPHP(Node $workspace, IDEConfiguration $configuration): void
    {
        $phpConfiguration = $configuration->getPHP();

        if (null !== ($xdebugConfiguration = $phpConfiguration->getXDebug())) {
            $xdebug = $workspace->getNamedChild('component', ['name' => 'PhpDebugGeneral']);

            if (null !== ($port = $xdebugConfiguration->getPort())) {
                $xdebug['xdebug_debug_port'] = $port;
            }
        }
    }

    public function addRuns(Node $workspace, RunCollection $runs): void
    {
        $selectedTypes = [
            'Behat' => 'Behat',
            'PHPUnit' => 'PHPUnit',
            'PHP Console' => 'PHP Script',
            'PHP Web Application' => 'PHP Web Application',
        ];

        $run = $workspace->getNamedChild('component', ['name' => 'RunManager']);

        $configureBehatDefaults = false;

        foreach ($runs->toArray() as $name => $runSettings) {
            $configuration = $run->getNamedChild('configuration', ['name' => $name]);

            switch ($runSettings->getType()) {
                case 'Behat':
                    $configuration['type'] = 'PhpBehatConfigurationType';
                    $configuration['factoryName'] = 'Behat';

                    $behatRunner = $configuration->getNamedChild('BehatRunner');

                    $behatRunner['directory'] = '$PROJECT_DIR$/' . $runSettings->getFolder();
                    $behatRunner['scenario'] = '';

                    $configureBehatDefaults = true;
                    break;
                case 'PHPUnit':
                    $configuration['type'] = 'PHPUnitRunConfigurationType';
                    $configuration['factoryName'] = 'PHPUnit';

                    $testRunner = $configuration->getNamedChild('TestRunner');

                    $testRunner['directory'] = '$PROJECT_DIR$/' . $runSettings->getFolder();

                    //$testRunner['scenario']  = '';
                    break;
                case 'PHP Console':
                    $configuration['type'] = 'PhpLocalRunConfigurationType';
                    $configuration['factoryName'] = 'PHP Console';
                    $configuration['path'] = '$PROJECT_DIR$/' . $runSettings->getScript();
                    $configuration['scriptParameters'] = $runSettings->getParameters();

                    if (!empty($environment = $runSettings->getEnvironment())) {
                        $environmentVariables = $configuration
                            ->getNamedChild('CommandLine')
                            ->getNamedChild('envs');

                        foreach ($environment as $envName => $envValue) {
                            $environmentVariables->getNamedChild('env', ['name' => $envName])['value'] = $envValue;
                        }
                    }

                    break;
                case 'PHP Web Application':
                    $configuration['type'] = 'PhpWebAppRunConfigurationType';
                    $configuration['factoryName'] = 'PHP Web Application';
                    $configuration['server_name'] = $runSettings->getServer();
                    $configuration['start_url'] = $runSettings->getUrl();
                    break;
                default:
                    throw new \RuntimeException('Not implemented: ' . $runSettings->getType());
            }
        }

        if ($configureBehatDefaults) {
            $configuration = $run->getNamedChild(
                'configuration',
                [
                    'type' => 'PhpBehatConfigurationType',
                    'default' => 'true',
                    'factoryName' => 'Behat',
                ]
            );

            $configuration->getNamedChild('BehatRunner', ['scenario' => '']);
            $configuration->getNamedChild('CommandLine', ['workingDirectory' => '$PROJECT_DIR$']);
            $configuration->getNamedChild('option', ['name' => 'workingDirectory', 'value' => '$PROJECT_DIR$']);
        }

        // Mark the first one as selected
        $selected = $runs->first();
        $run['selected'] = $selectedTypes[$selected->getType()] . '.' . $selected->getName();
    }

    public function addDefaultInterpreter(Node $workspace, Interpreter $first): void
    {
        // Set the first one as the active one
        $workspace->getNamedChild(
            'component',
            ['name' => 'PhpWorkspaceProjectConfiguration']
        )['interpreter_name'] = $first->getName();
    }
}
