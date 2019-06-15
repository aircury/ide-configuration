<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration;

use Aircury\IDEConfiguration\Idea\IdeaProject;
use Aircury\IDEConfiguration\Manipulator\DataSourcesLocalManipulator;
use Aircury\IDEConfiguration\Manipulator\DataSourcesManipulator;
use Aircury\IDEConfiguration\Manipulator\DeploymentManipulator;
use Aircury\IDEConfiguration\Manipulator\EncodingsManipulator;
use Aircury\IDEConfiguration\Manipulator\IMLManipulator;
use Aircury\IDEConfiguration\Manipulator\LaravelManipulator;
use Aircury\IDEConfiguration\Manipulator\MiscManipulator;
use Aircury\IDEConfiguration\Manipulator\ModulesManipulator;
use Aircury\IDEConfiguration\Manipulator\PHPManipulator;
use Aircury\IDEConfiguration\Manipulator\PHPTestFrameworkManipulator;
use Aircury\IDEConfiguration\Manipulator\SQLDialectsManipulator;
use Aircury\IDEConfiguration\Manipulator\SymfonyManipulator;
use Aircury\IDEConfiguration\Manipulator\VCSManipulator;
use Aircury\IDEConfiguration\Manipulator\WebResourcesManipulator;
use Aircury\IDEConfiguration\Manipulator\WebServersManipulator;
use Aircury\IDEConfiguration\Manipulator\WorkspaceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ConfigureIDECommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('aircury:configure:ide')
            ->setDescription('Reads the ide-config.yaml and configures the IDE from it')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to search for configuration files. E.g. projects/abc');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();
        $searchDir = getcwd();

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

        $modulesManipulator = new ModulesManipulator();
        $imlManipulator = new IMLManipulator();
        $webResourcesManipulator = new WebResourcesManipulator();
        $workspaceManipulator = new WorkspaceManipulator();
        $phpManipulator = new PHPManipulator();
        $phpTestFrameworkManipulator = new PHPTestFrameworkManipulator();
        $miscManipulator = new MiscManipulator();
        $webServersManipulator = new WebServersManipulator();
        $deploymentManipulator = new DeploymentManipulator();
        $vcsManipulator = new VCSManipulator();
        $dataSourcesManipulator = new DataSourcesManipulator();
        $dataSourcesLocalManipulator = new DataSourcesLocalManipulator();
        $sqlDialectsManipulator = new SQLDialectsManipulator();
        $symfonyManipulator = new SymfonyManipulator();
        $laravelManipulator = new LaravelManipulator();
        $encodingsManipulator = new EncodingsManipulator();

        foreach ($finder as $file) {
            /** @var SplFileInfo $file */

            $path = $file->getRealPath();

            $output->writeln(
                sprintf('<info>Found configuration file at <comment>%s</comment>. Configuring...</info>', $path)
            );

            $ideConfiguration = new IDEConfiguration($path);
            $projectRootDir = dirname($path);
            $project = new IdeaProject(dirname($path) . '/.idea');

            $modulesManipulator->addModules($project->getModules(), $ideConfiguration);

            foreach ($ideConfiguration->getModules()->toArray() as $moduleName => $module) {
                $moduleIML = $project->getIML($module);

                $imlManipulator->addIML($moduleIML, $ideConfiguration, $moduleName);

                if ($module->hasResources()) {
                    $webResourcesManipulator->addWebResources(
                        $project->getWebResources(),
                        $ideConfiguration,
                        $moduleName
                    );
                }
            }

            if (null !== ($composer = $ideConfiguration->getComposer())) {
                $workspaceManipulator->addComposer($project->getWorkspace(), $composer);
            }

            $servers = $ideConfiguration->getServers();

            if (!$servers->isEmpty()) {
                $projectRemoteRootDir = $ideConfiguration->getServers()->first()->getMappings()['$PROJECT_DIR$'];
                $projectsRemoteRootDir = dirname($projectRemoteRootDir);

                $workspaceManipulator->addServers($workspace = $project->getWorkspace(), $ideConfiguration);

                if (null !== ($phpConfig = $ideConfiguration->getPHP())) {
                    $workspaceManipulator->addPHP($workspace, $ideConfiguration);
                    $phpManipulator->addPHP($php = $project->getPHP(), $ideConfiguration, $projectsRemoteRootDir);

                    $interpreters = $phpConfig->getInterpreters();
                    $phpTestFramework = $project->getPHPTestFramework();

                    if (!$interpreters->isEmpty()) {
                        foreach ($interpreters->toArray() as $interpreterName => $interpreter) {
                            if (null !== ($behat = $interpreter->getBehat())) {
                                $phpManipulator->addBehat($php, $behat, $interpreter, $projectRemoteRootDir);
                                $phpTestFrameworkManipulator->addBehat(
                                    $phpTestFramework,
                                    $interpreter,
                                    $projectRootDir,
                                    $projectRemoteRootDir,
                                    $behat
                                );
                            }

                            if (null !== ($phpunit = $interpreter->getPHPUnit())) {
                                $phpManipulator->addPHPUnit(
                                    $php,
                                    $phpunit,
                                    $interpreter,
                                    $projectRootDir,
                                    $projectRemoteRootDir
                                );
                                $phpTestFrameworkManipulator->addPHPUnit(
                                    $phpTestFramework,
                                    $interpreter,
                                    $projectRootDir
                                );
                            }
                        }

                        $workspaceManipulator->addDefaultInterpreter($workspace, $interpreters->first());
                    }
                }

                if (null !== ($javascript = $ideConfiguration->getJavaScript())) {
                    $miscManipulator->addJavaScript($project->getMisc(), $javascript);
                }
            }

            $deployments = $ideConfiguration->getDeployments();

            if (!$deployments->isEmpty()) {
                $webServersManipulator->addDeployment($project->getWebServers(), $deployments);
                $deploymentManipulator->addDeployment($project->getDeployment(), $deployments);
            }

            if (null !== ($vcs = $ideConfiguration->getVCS())) {
                $vcsManipulator->addVCS($project->getVCS(), $vcs);
            }

            $databases = $ideConfiguration->getDatabases();

            if (!$databases->isEmpty()) {
                $dataSourcesManipulator->addDatabases($project->getDataSources(), $databases);
                $dataSourcesLocalManipulator->addDatabases($project->getDataSourcesLocal(), $databases);
            }

            if (null !== ($sqlDialects = $ideConfiguration->getSQLDialects())) {
                $sqlDialectsManipulator->addSQLDialects($project->getSQLDialects(), $sqlDialects);
            }

            if (null !== ($symfony = $ideConfiguration->getSymfony())) {
                $symfonyManipulator->addSymfony($project->getSymfony(), $symfony);
            }

            if (null !== ($laravel = $ideConfiguration->getLaravel())) {
                $laravelManipulator->addLaravel($project->getLaravel(), $laravel);
            }

            $runs = $ideConfiguration->getRuns();

            if (!$runs->isEmpty()) {
                $workspaceManipulator->addRuns($project->getWorkspace(), $runs);
            }

            $encodingsManipulator->addDefaultEncoding($project->getEncodings());

            $project->dump();
        }

        $output->writeln('<info>All done</info>');
    }
}
