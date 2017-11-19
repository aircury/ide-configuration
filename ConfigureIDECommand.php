<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration;

use Aircury\Xml\Node;
use Aircury\Xml\Xml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;
use Webpatser\Uuid\Uuid;

class ConfigureIDECommand extends Command
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    protected function configure()
    {
        $this
            ->setName('aircury:configure:ide')
            ->setDescription('Reads the ide-config.yaml and configures the IDE from it')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to search for configuration files. E.g. projects/abc');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->filesystem = new Filesystem();
        $finder           = new Finder();
        $searchDir        = getcwd();

        if (null !== ($path = $input->getArgument('path'))) {
            $searchDir .= '/' . $path;
        }

        $output->writeln(
            sprintf(
                '<info>Searching for <comment>ide-config.yaml</comment> in <comment>%s</comment> and its subfolders...</info>',
                $searchDir
            )
        );

        $finder
            ->files()
            ->name('ide-config.yaml')
            ->in($searchDir)
            ->exclude(
                [
                    'vendor',
                    'node_modules',
                    'bower_components',
                    '.idea',
                    'var',
                    'test',
                    'tests',
                ]
            );

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */

            $output->writeln(
                sprintf(
                    '<info>Found configuration file at <comment>%s</comment>. Configuring...</info>',
                    $ideConfigYaml = $file->getRealPath()
                )
            );

            $ideConfig                = Yaml::parse($file->getContents());
            $ideConfig                = $this->resolveDependencies($ideConfig);
            $ideaPath                 = dirname($ideConfigYaml) . '/.idea';
            $modulesFilePath          = $ideaPath . '/modules.xml';
            $workspaceFilePath        = $ideaPath . '/workspace.xml';
            $encodingsFilePath        = $ideaPath . '/encodings.xml';
            $deploymentFilePath       = $ideaPath . '/deployment.xml';
            $webServersFilePath       = $ideaPath . '/webServers.xml';
            $phpFilePath              = $ideaPath . '/php.xml';
            $phpTestFrameworkFilePath = $ideaPath . '/php-test-framework.xml';
            $vcsFilePath              = $ideaPath . '/vcs.xml';
            $dataSourcesFilePath      = $ideaPath . '/dataSources.xml';
            $dataSourcesLocalFilePath = $ideaPath . '/dataSources.local.xml';
            $sqlDialectsFilePath      = $ideaPath . '/sqldialects.xml';
            $symfonyFilePath          = $ideaPath . '/symfony2.xml';
            $webResourcesPath         = $ideaPath . '/webResources.xml';

            $modules = file_exists($modulesFilePath)
                ? Xml::parseFile($modulesFilePath)
                : new Node('project', ['version' => 4]);

            if (!isset($ideConfig['modules'])) {
                throw new \InvalidArgumentException('\'modules\' configuration is required');
            }

            $projectModules = $modules
                ->getNamedChild('component', ['name' => 'ProjectModuleManager'])
                ->getNamedChild('modules');

            $additionalPHPIncludePaths = [];

            foreach ($ideConfig['modules'] as $moduleName => $moduleSettings) {
                $imlDir = '$PROJECT_DIR$' === $moduleSettings['root']
                    ? '$PROJECT_DIR$/.idea'
                    : $moduleSettings['root'];

                $projectModules
                    ->getNamedChild(
                        'module',
                        ['filepath' => sprintf('%s/%s.iml', $imlDir, $moduleName)]
                    )['fileurl'] = sprintf('file://%s/%s.iml', $imlDir, $moduleName);

                $moduleIMLFilePath = str_replace('$PROJECT_DIR$', $file->getPath(), sprintf('%s/%s.iml', $imlDir, $moduleName));
                $moduleIML         = file_exists($moduleIMLFilePath)
                    ? Xml::parseFile($moduleIMLFilePath)
                    : new Node('module');

                $moduleIML['type']    = 'WEB_MODULE';
                $moduleIML['version'] = 4;

                $component = $moduleIML->getNamedChild('component', ['name' => 'NewModuleRootManager']);

                $component['inherit-compiler-output'] = 'true';

                $component->getNamedChild('exclude-output');

                $content        = $component->getNamedChild('content');
                $content['url'] = 'file://$MODULE_DIR$';

                foreach ($moduleSettings['excluded'] ?? [] as $excludedFolder) {
                    $content->getNamedChild('excludeFolder', ['url' => 'file://$MODULE_DIR$/' . $excludedFolder]);
                }

                // Library root means excluded + PHP include_path. See: https://stackoverflow.com/questions/35654320/how-to-configure-directories-when-using-a-symfony-project-in-phpstorm
                foreach ($moduleSettings['libraries'] ?? [] as $excludedFolder) {
                    $content->getNamedChild('excludeFolder', ['url' => 'file://$MODULE_DIR$/' . $excludedFolder]);

                    $additionalPHPIncludePaths[] = $excludedFolder;
                }

                foreach ($moduleSettings['sources'] ?? [] as $sourceFolder) {
                    if ('.' === $sourceFolder) {
                        $sourceFolder = '';
                    }

                    $content->getNamedChild(
                        'sourceFolder',
                        ['url' => 'file://$MODULE_DIR$/' . $sourceFolder, 'isTestSource' => 'false']
                    );
                }

                foreach ($moduleSettings['tests'] ?? [] as $sourceFolder) {
                    $content->getNamedChild(
                        'sourceFolder',
                        ['url' => 'file://$MODULE_DIR$/' . $sourceFolder, 'isTestSource' => 'true']
                    );
                }

                $component->getNamedChild('orderEntry', ['type' => 'inheritedJdk']);
                $component->getNamedChild('orderEntry', ['type' => 'sourceFolder'])['forTests'] = 'false';

                $this->filesystem->dumpFile($moduleIMLFilePath, Xml::dump($moduleIML));

                if (isset($moduleSettings['resources'])) {
                    $webResources = file_exists($webResourcesPath)
                        ? Xml::parseFile($webResourcesPath)
                        : new Node('project', ['version' => 4]);

                    $resourceRoots = $webResources
                        ->getNamedChild('component', ['name' => 'WebResourcesPaths'])
                        ->getNamedChild('contentEntries')
                        ->getNamedChild('entry', ['url' => 'file://$PROJECT_DIR$'])
                        ->getNamedChild('entryData')
                        ->getNamedChild('resourceRoots');

                    foreach ($moduleSettings['resources'] as $resource) {
                        $resourceRoots->getNamedChild('path', ['value' => 'file://$PROJECT_DIR$/' . $resource]);
                    }

                    $this->filesystem->dumpFile($webResourcesPath, Xml::dump($webResources));
                }
            }

            $workspace = file_exists($workspaceFilePath)
                ? Xml::parseFile($workspaceFilePath)
                : new Node('project', ['version' => 4]);

            // Hardcoded configurations
            $workspace->getNamedChild('component', ['name' => 'ComposerSettings'])['doNotAsk'] = 'true';

            if (array_key_exists('composer', $ideConfig)) {
                $this->addComposer($workspace, $ideConfig['composer'] ?? []);
            }

            if (isset($ideConfig['servers'])) {
                $servers = $workspace
                    ->getNamedChild('component', ['name' => 'PhpServers'])
                    ->getNamedChild('servers');

                $this->addServers($servers, $ideConfig['servers']);

                if (isset($ideConfig['php'])) {
                    $this->addPHP(
                        $workspace,
                        $phpFilePath,
                        $phpTestFrameworkFilePath,
                        $ideConfig['php'],
                        $additionalPHPIncludePaths
                    );
                }
            }

            if (isset($ideConfig['deployment'])) {
                $this->addDeployment($webServersFilePath, $deploymentFilePath, $ideConfig['deployment']);
            }

            if (isset($ideConfig['vcs'])) {
                $this->addVCS($vcsFilePath, $ideConfig['vcs']);
            }

            if (isset($ideConfig['databases'])) {
                $this->addDatabases($dataSourcesFilePath, $dataSourcesLocalFilePath, $ideConfig['databases']);
            }

            if (isset($ideConfig['sql'])) {
                $this->addSQLDialects($sqlDialectsFilePath, $ideConfig['sql']);
            }

            if (array_key_exists('symfony', $ideConfig)) {
                $this->addSymfony($symfonyFilePath, $ideConfig['symfony'] ?? []);
            }

            if (isset($ideConfig['run'])) {
                $this->addRun($workspace, $ideConfig['run']);
            }

            $encodings = file_exists($encodingsFilePath)
                ? Xml::parseFile($encodingsFilePath)
                : new Node('project', ['version' => 4]);

            $encodings
                ->getNamedChild('component', ['name' => 'Encoding'])
                ->getNamedChild('file', ['url' => 'PROJECT'])['charset'] = 'UTF-8';

            $this->filesystem->dumpFile($modulesFilePath, Xml::dump($modules));
            $this->filesystem->dumpFile($workspaceFilePath, Xml::dump($workspace));
            $this->filesystem->dumpFile($encodingsFilePath, Xml::dump($encodings));
        }

        $output->writeln('<info>All done</info>');
    }

    private function addComposer(Node $workspace, array $composer): void
    {
        $composerSettings = $workspace
            ->getNamedChild('component', ['name' => 'ComposerSettings']);

        $composerSettings->addChild(new Node('pharConfigPath', [], '$PROJECT_DIR$/composer.json'));

        if ($composer['synchronize'] ?? false) {
            $composerSettings['synchronizationState'] = 'SYNCHRONIZE';
        }
    }

    private function addServers(Node $servers, array $ideConfigServers): void
    {
        foreach ($ideConfigServers as $serverName => $serverSettings) {
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

            if (isset($serverSettings['host'])) {
                $server['host'] = $serverSettings['host'];
            }

            if (isset($serverSettings['mappings']) && !empty($serverSettings['mappings'])) {
                $server['use_path_mappings'] = 'true';

                $pathMappings = $server->getNamedChild('path_mappings');

                foreach ($serverSettings['mappings'] as $localRoot => $remoteRoot) {
                    $pathMappings->getNamedChild('mapping', ['local-root' => $localRoot])['remote-root'] = $remoteRoot;
                }
            }
        }
    }

    private function addDeployment(
        string $webServersFilePath,
        string $deploymentFilePath,
        array $ideConfigDeployment
    ): void {
        $webServers = file_exists($webServersFilePath)
            ? Xml::parseFile($webServersFilePath)
            : new Node('project', ['version' => 4]);
        $deployment = file_exists($deploymentFilePath)
            ? Xml::parseFile($deploymentFilePath)
            : new Node('project', ['version' => 4]);

        foreach ($ideConfigDeployment as $deploymentName => $deploymentSettings) {
            $webServer    = $webServers
                ->getNamedChild('component', ['name' => 'WebServers'])
                ->getNamedChild('option', ['name' => 'servers'])
                ->getNamedChild('webServer', ['name' => $deploymentName]);
            $fileTransfer = $webServer->getNamedChild('fileTransfer');

            $webServer['id']  = $webServer['id'] ?? Uuid::generate(4)->string;
            $webServer['url'] = $deploymentSettings['url'];

            $fileTransfer['host']       = $deploymentSettings['host'];
            $fileTransfer['port']       = $deploymentSettings['port'];
            $fileTransfer['privateKey'] = $deploymentSettings['private_key'];
            $fileTransfer['accessType'] = $deploymentSettings['type'];
            $fileTransfer['keyPair']    = 'true';

            $fileTransfer
                ->getNamedChild('advancedOptions')
                ->getNamedChild('advancedOptions')['dataProtectionLevel'] = 'Private';

            $fileTransfer
                ->getNamedChild('option', ['name' => 'port'])['value'] = $deploymentSettings['port'];

            $component = $deployment->getNamedChild(
                'component',
                ['name' => 'PublishConfigData', 'serverName' => $deploymentName]
            );

            $serverData = $component->getNamedChild('serverData');

            if (isset($deploymentSettings['mappings']) && !empty($deploymentSettings['mappings'])) {
                $mappings = $serverData
                    ->getNamedChild('paths', ['name' => $deploymentName])
                    ->getNamedChild('serverdata')
                    ->getNamedChild('mappings');

                foreach ($deploymentSettings['mappings'] as $localPath => $deploymentPath) {
                    $mapping = $mappings->getNamedChild('mapping', ['local' => $localPath]);

                    $mapping['deploy'] = $deploymentPath;
                    $mapping['web']    = '/';
                }
            }
        }

        $this->filesystem->dumpFile($webServersFilePath, Xml::dump($webServers));
        $this->filesystem->dumpFile($deploymentFilePath, Xml::dump($deployment));
    }

    private function resolveDependencies(array $ideConfig): array
    {
        $resolved = [];

        foreach ($ideConfig as $key => $value) {
            if (is_array($value)) {
                $resolved[$key] = $this->resolveDependencies($value);
            } elseif (is_string($value)) {
                if (0 === strpos($value, 'env(') && ')' === substr($value, -1)) {
                    $environmentValue = getenv(substr($value, 4, -1));

                    if (false === $environmentValue) {
                        throw new \RuntimeException(
                            sprintf(
                                'On ide-config.yaml you are making use of an environment variable, %s, but is not set',
                                $value
                            )
                        );
                    }

                    $resolved[$key] = $environmentValue;
                } else {
                    $resolved[$key] = $value;
                }
            } else {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    private function addPHP(
        Node $workspace,
        string $phpFilePath,
        string $phpTestFrameworkFilePath,
        array $ideConfigPHP,
        array $additionalPHPIncludePaths
    ): void {
        $projectRootDir = dirname($phpFilePath, 2);

        $php = file_exists($phpFilePath)
            ? Xml::parseFile($phpFilePath)
            : new Node('project', ['version' => 4]);

        if (isset($ideConfigPHP['language_level'])) {
            $php
                ->getNamedChild('component', ['name' => 'PhpProjectSharedConfiguration'])
            ['php_language_level'] = $ideConfigPHP['language_level'];
        }

        if (isset($ideConfigPHP['xdebug'])) {
            $xdebug = $workspace->getNamedChild('component', ['name' => 'PhpDebugGeneral']);

            if (isset($ideConfigPHP['xdebug']['port'])) {
                $xdebug['xdebug_debug_port'] = $ideConfigPHP['xdebug']['port'];
            }
        }

        if (!empty($additionalPHPIncludePaths)) {
            $includePath = $php
                ->getNamedChild('component', ['name' => 'PhpIncludePathManager'])
                ->getNamedChild('include_path');

            foreach ($additionalPHPIncludePaths as $path) {
                $includePath->getNamedChild('path', ['value' => '$PROJECT_DIR$/' . $path]);
            }
        }

        if (!empty($ideConfigPHP['interpreters'] ?? [])) {
            $interpreters = $php
                ->getNamedChild('component', ['name' => 'PhpInterpreters'])
                ->getNamedChild('interpreters');

            foreach ($ideConfigPHP['interpreters'] as $interpreterName => $interpreterSettings) {
                $interpreter = $interpreters->getNamedChild('interpreter', ['name' => $interpreterName]);

                $interpreter['id']          = $interpreter['id'] ?? Uuid::generate(4)->string;
                $interpreter['debugger_id'] = 'php.debugger.XDebug';

                if ('SSH' === $interpreterSettings['type'] ?? '') {
                    $interpreter['home'] = sprintf(
                        'ssh://%s@%s:%s%s',
                        $interpreterSettings['username'],
                        $interpreterSettings['host'],
                        $interpreterSettings['port'],
                        $interpreterSettings['php_path']
                    );
                }

                $remoteData = $interpreter->getNamedChild('remote_data');

                $remoteData['HOST']                = $interpreterSettings['host'];
                $remoteData['PORT']                = $interpreterSettings['port'];
                $remoteData['USERNAME']            = $interpreterSettings['username'];
                $remoteData['PRIVATE_KEY_FILE']    = $interpreterSettings['private_key'];
                $remoteData['MY_KNOWN_HOSTS_FILE'] = '';
                $remoteData['USE_KEY_PAIR']        = 'true';
                $remoteData['USE_AUTH_AGENT']      = 'false';
                $remoteData['INTERPRETER_PATH']    = $interpreterSettings['php_path'];
                $remoteData['HELPERS_PATH']        = dirname($projectRootDir) . '/.phpstorm_helpers';
                $remoteData['INITIALIZED']         = 'false';
                $remoteData['VALID']               = 'true';

                if (isset($interpreterSettings['behat']) || isset($interpreterSettings['phpunit'])) {
                    $phpTestFramework = file_exists($phpTestFrameworkFilePath)
                        ? Xml::parseFile($phpTestFrameworkFilePath)
                        : new Node('project', ['version' => 4]);

                    $toolsCache = $phpTestFramework
                        ->getNamedChild('component', ['name' => 'PhpTestFrameworkVersionCache'])
                        ->getNamedChild('tools_cache');

                    $composerLockPath = $projectRootDir . '/composer.lock';

                    if (isset($interpreterSettings['behat'])) {
                        $behat = $php
                            ->getNamedChild('component', ['name' => 'Behat'])
                            ->getNamedChild('behat_settings')
                            ->getNamedChild('behat_by_interpreter', ['interpreter_id' => $interpreter['id']]);

                        $behat['configuration_file_path'] = $projectRootDir . '/' . $interpreterSettings['behat']['configuration'];
                        $behat['behat_path']              = $projectRootDir . '/' . $interpreterSettings['behat']['bin_path'];
                        $behat['use_configuration_file']  = 'true';

                        $toolsCache
                            ->getNamedChild('tool', ['tool_name' => 'Behat'])
                            ->getNamedChild('cache')
                            ->getNamedChild('versions')
                            ->getNamedChild('info', ['id' => 'interpreter-' . $interpreter['id']])
                        ['version'] = $this->getPackageVersion($composerLockPath, 'behat/behat');
                    }

                    if (isset($interpreterSettings['phpunit'])) {
                        $phpunit = $php
                            ->getNamedChild('component', ['name' => 'PhpUnit'])
                            ->getNamedChild('phpunit_settings')
                            ->getNamedChild('phpunit_by_interpreter', ['interpreter_id' => $interpreter['id']]);

                        $phpunit['load_method']             = 'CUSTOM_LOADER';
                        $phpunit['configuration_file_path'] = $projectRootDir . '/' . $interpreterSettings['phpunit']['configuration'];
                        $phpunit['custom_loader_path']      = $projectRootDir . '/' . $interpreterSettings['phpunit']['loader'];
                        $phpunit['phpunit_phar_path']       = '';
                        $phpunit['use_configuration_file']  = 'true';

                        $toolsCache
                            ->getNamedChild('tool', ['tool_name' => 'PHPUnit'])
                            ->getNamedChild('cache')
                            ->getNamedChild('versions')
                            ->getNamedChild('info', ['id' => 'interpreter-' . $interpreter['id']])
                        ['version'] = $this->getPackageVersion($composerLockPath, 'phpunit/phpunit');
                    }

                    $this->filesystem->dumpFile($phpTestFrameworkFilePath, Xml::dump($phpTestFramework));
                }
            }

            // Set the first one as the active one
            $workspace->getNamedChild(
                'component',
                ['name' => 'PhpWorkspaceProjectConfiguration']
            )['interpreter_name'] = array_keys($ideConfigPHP['interpreters'])[0];
        }

        $this->filesystem->dumpFile($phpFilePath, Xml::dump($php));
    }

    private function getPackageVersion(string $composerLockPath, string $packageName): string
    {
        if (!file_exists($composerLockPath)) {
            return '';
        }

        $contents = json_decode(file_get_contents($composerLockPath), true);

        if (isset($contents['packages'])) {
            foreach ($contents['packages'] as $package) {
                if ($package['name'] === $packageName) {
                    $version = $package['version'];

                    if ('v' === ($firstCharacter = substr($version, 0, 1))) {
                        return substr($version, 1);
                    }

                    return is_numeric($firstCharacter) ? $version : '';
                }
            }
        }

        if (isset($contents['packages-dev'])) {
            foreach ($contents['packages-dev'] as $package) {
                if ($package['name'] === $packageName) {
                    $version = $package['version'];

                    if ('v' === ($firstCharacter = substr($version, 0, 1))) {
                        return substr($version, 1);
                    }

                    return is_numeric($firstCharacter) ? $version : '';
                }
            }
        }

        return '';
    }

    private function addVCS(string $vcsFilePath, array $ideConfigVCS): void
    {
        $vcs = file_exists($vcsFilePath)
            ? Xml::parseFile($vcsFilePath)
            : new Node('project', ['version' => 4]);

        $vcsDirectoryMappings = $vcs->getNamedChild('component', ['name' => 'VcsDirectoryMappings']);

        foreach ($ideConfigVCS as $directory => $vcsSystem) {
            $vcsDirectoryMappings->getNamedChild('mapping', ['directory' => $directory])['vcs'] = $vcsSystem;
        }

        $this->filesystem->dumpFile($vcsFilePath, Xml::dump($vcs));
    }

    private function addDatabases(
        string $dataSourcesFilePath,
        string $dataSourcesLocalFilePath,
        array $ideConfigDatabases
    ): void {
        $dataSources      = file_exists($dataSourcesFilePath)
            ? Xml::parseFile($dataSourcesFilePath)
            : new Node('project', ['version' => 4]);
        $dataSourcesLocal = file_exists($dataSourcesLocalFilePath)
            ? Xml::parseFile($dataSourcesLocalFilePath)
            : new Node('project', ['version' => 4]);

        $dataSourceManager = $dataSources->getNamedChild('component', ['name' => 'DataSourceManagerImpl']);

        $dataSourceManager['format']          = 'xml';
        $dataSourceManager['multifile-model'] = 'true';

        $dataSourceStorage = $dataSourcesLocal->getNamedChild('component', ['name' => 'dataSourceStorageLocal']);

        foreach ($ideConfigDatabases as $databaseName => $databaseSettings) {
            $dataSource      = $dataSourceManager->getNamedChild('data-source', ['name' => $databaseName]);
            $localDataSource = $dataSourceStorage->getNamedChild('data-source', ['name' => $databaseName]);

            $dataSource['source']    = 'LOCAL';
            $dataSource['uuid']      = $webServer['uuid'] ?? Uuid::generate(4)->string;
            $localDataSource['uuid'] = $dataSource['uuid'];

            switch ($databaseSettings['driver']) {
                case 'mysql':
                    $dataSource->getNamedChild('jdbc-driver')->contents = 'com.mysql.jdbc.Driver';
                    break;
                case 'postgresql':
                    $dataSource->getNamedChild('jdbc-driver')->contents = 'org.postgresql.Driver';

                    if (isset($databaseSettings['schemas'])) {
                        $localDataSource
                            ->getNamedChild('introspection-schemas')
                            ->contents = $databaseSettings['database'] . ':' . implode(',', $databaseSettings['schemas']);
                    }

                    break;
                default:
                    throw new \RuntimeException('Not implemented: ' . $databaseSettings['driver']);
            }

            $dataSource->getNamedChild('synchronize')->contents = 'true';
            $dataSource->getNamedChild('driver-ref')->contents  = $databaseSettings['driver'];
            $dataSource->getNamedChild('jdbc-url')->contents    = sprintf(
                'jdbc:%s://%s:%s/%s',
                $databaseSettings['driver'],
                $databaseSettings['host'],
                $databaseSettings['port'],
                $databaseSettings['database']
            );

            $localDataSource->getNamedChild('secret-storage')->contents = 'master_key';
            $localDataSource->getNamedChild('user-name')->contents      = $databaseSettings['username'];
        }

        $this->filesystem->dumpFile($dataSourcesFilePath, Xml::dump($dataSources));
        $this->filesystem->dumpFile($dataSourcesLocalFilePath, Xml::dump($dataSourcesLocal));
    }

    private function addSQLDialects(string $sqlDialectsFilePath, array $ideConfigSQLDialects): void
    {
        $sqlDialects = file_exists($sqlDialectsFilePath)
            ? Xml::parseFile($sqlDialectsFilePath)
            : new Node('project', ['version' => 4]);

        $sqlDialectsMappings = $sqlDialects->getNamedChild('component', ['name' => 'SqlDialectMappings']);

        foreach ($ideConfigSQLDialects as $path => $ideConfigSQLDialect) {
            $dialectPath = $sqlDialectsMappings->getNamedChild(
                'file',
                ['url' => 'PROJECT' === $path ? 'PROJECT' : 'file://$PROJECT_DIR$/' . $path]
            );

            $dialectPath['dialect'] = $ideConfigSQLDialect;
        }

        $this->filesystem->dumpFile($sqlDialectsFilePath, Xml::dump($sqlDialects));
    }

    private function addSymfony(string $symfonyFilePath, array $ideConfigSymfony): void
    {
        $symfony = file_exists($symfonyFilePath)
            ? Xml::parseFile($symfonyFilePath)
            : new Node('project', ['version' => 4]);

        $symfonyPlugin = $symfony->getNamedChild('component', ['name' => 'Symfony2PluginSettings']);

        $symfonyPlugin->getNamedChild('option', ['name' => 'pluginEnabled'])['value'] = 'true';

        if (isset($ideConfigSymfony['web'])) {
            $symfonyPlugin->getNamedChild('option', ['name' => 'directoryToWeb'])['value'] = $ideConfigSymfony['web'];
        }

        if (isset($ideConfigSymfony['app'])) {
            $symfonyPlugin->getNamedChild('option', ['name' => 'directoryToApp'])['value'] = $ideConfigSymfony['app'];
        }

        $this->filesystem->dumpFile($symfonyFilePath, Xml::dump($symfony));
    }

    private function addRun(Node $workspace, $ideConfigRun)
    {
        $selectedTypes = [
            'Behat'               => 'Behat',
            'PHPUnit'             => 'PHPUnit',
            'PHP Console'         => 'PHP Script',
            'PHP Web Application' => 'PHP Web Application',
        ];

        $run = $workspace->getNamedChild('component', ['name' => 'RunManager']);

        $configureBehatDefaults = false;

        foreach ($ideConfigRun as $name => $runSettings) {
            $configuration = $run->getNamedChild('configuration', ['name' => $name]);

            switch ($runSettings['type']) {
                case 'Behat':
                    $configuration['type']        = 'PhpBehatConfigurationType';
                    $configuration['factoryName'] = 'Behat';

                    $behatRunner = $configuration->getNamedChild('BehatRunner');

                    $behatRunner['directory'] = '$PROJECT_DIR$/' . $runSettings['folder'];
                    $behatRunner['scenario']  = '';

                    $configureBehatDefaults = true;
                    break;
                case 'PHPUnit':
                    $configuration['type']        = 'PHPUnitRunConfigurationType';
                    $configuration['factoryName'] = 'PHPUnit';

                    $testRunner = $configuration->getNamedChild('TestRunner');

                    $testRunner['directory'] = '$PROJECT_DIR$/' . $runSettings['folder'];
                    $testRunner['scenario']  = '';
                    break;
                case 'PHP Console':
                    $configuration['type']             = 'PhpLocalRunConfigurationType';
                    $configuration['factoryName']      = 'PHP Console';
                    $configuration['path']             = '$PROJECT_DIR$/' . $runSettings['script'];
                    $configuration['scriptParameters'] = $runSettings['parameters'];

                    if (isset($runSettings['environment'])) {
                        $environmentVariables = $configuration
                            ->getNamedChild('CommandLine')
                            ->getNamedChild('envs');

                        foreach ($runSettings['environment'] as $envName => $envValue) {
                            $environmentVariables->getNamedChild('env', ['name' => $envName])['value'] = $envValue;
                        }
                    }

                    break;
                case 'PHP Web Application':
                    $configuration['type']        = 'PhpWebAppRunConfigurationType';
                    $configuration['factoryName'] = 'PHP Web Application';
                    $configuration['server_name'] = $runSettings['server'];
                    $configuration['start_url']   = $runSettings['url'];
                    break;
                default:
                    throw new \RuntimeException('Not implemented: ' . $runSettings['type']);
            }
        }

        if ($configureBehatDefaults) {
            $configuration = $run->getNamedChild(
                'configuration',
                [
                    'type'        => 'PhpBehatConfigurationType',
                    'default'     => 'true',
                    'factoryName' => 'Behat',
                ]
            );

            $configuration->getNamedChild('BehatRunner', ['scenario' => '']);
            $configuration->getNamedChild('CommandLine', ['workingDirectory' => '$PROJECT_DIR$']);
            $configuration->getNamedChild('option', ['name' => 'workingDirectory', 'value' => '$PROJECT_DIR$']);
        }

        // Mark the first one as selected
        $selected        = array_keys($ideConfigRun)[0];
        $run['selected'] = $selectedTypes[$ideConfigRun[$selected]['type']] . '.' . $selected;
    }
}
