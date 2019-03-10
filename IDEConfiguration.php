<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration;

use Aircury\IDEConfiguration\Model\Composer;
use Aircury\IDEConfiguration\Model\Database;
use Aircury\IDEConfiguration\Model\DatabaseCollection;
use Aircury\IDEConfiguration\Model\Deployment;
use Aircury\IDEConfiguration\Model\DeploymentCollection;
use Aircury\IDEConfiguration\Model\JavaScript;
use Aircury\IDEConfiguration\Model\Laravel;
use Aircury\IDEConfiguration\Model\Module;
use Aircury\IDEConfiguration\Model\ModuleCollection;
use Aircury\IDEConfiguration\Model\PHP;
use Aircury\IDEConfiguration\Model\Run;
use Aircury\IDEConfiguration\Model\RunCollection;
use Aircury\IDEConfiguration\Model\Server;
use Aircury\IDEConfiguration\Model\ServerCollection;
use Aircury\IDEConfiguration\Model\SQLDialects;
use Aircury\IDEConfiguration\Model\Symfony;
use Aircury\IDEConfiguration\Model\VCS;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class IDEConfiguration
{
    /**
     * @var OptionsResolver
     */
    private static $topLevelResolver;

    /**
     * @var ModuleCollection
     */
    private $modules;

    /**
     * @var Composer|null
     */
    private $composer;

    /**
     * @var ServerCollection
     */
    private $servers;

    /**
     * @var DeploymentCollection
     */
    private $deployments;

    /**
     * @var PHP|null
     */
    private $php;

    /**
     * @var JavaScript|null
     */
    private $javascript;

    /**
     * @var VCS|null
     */
    private $vcs;

    /**
     * @var DatabaseCollection
     */
    private $databases;

    /**
     * @var SQLDialects|null
     */
    private $sqlDialects;

    /**
     * @var Symfony|null
     */
    private $symfony;

    /**
     * @var Laravel|null
     */
    private $laravel;

    /**
     * @var RunCollection
     */
    private $runs;

    /**
     * @var string[]
     */
    private $knownEnvironmentVariables;

    public function __construct(string $ideConfigurationYamlPath)
    {
        $ideConfig = Yaml::parse(file_get_contents($ideConfigurationYamlPath));
        $ideConfig = $this->resolveDependencies($ideConfig);

        if (null === self::$topLevelResolver) {
            self::$topLevelResolver = new OptionsResolver();

            $this->configureTopLevelOptions(self::$topLevelResolver);
        }

        $ideConfig = self::$topLevelResolver->resolve($ideConfig);

        $this->modules = new ModuleCollection();
        $this->servers = new ServerCollection();
        $this->deployments = new DeploymentCollection();
        $this->databases = new DatabaseCollection();
        $this->runs = new RunCollection();

        foreach ($ideConfig['modules'] as $moduleName => $moduleConfig) {
            $this->modules[$moduleName] = new Module($moduleName, $moduleConfig);
        }

        if (!empty($ideConfig['composer'])) {
            $this->composer = new Composer($ideConfig['composer']);
        }

        foreach ($ideConfig['servers'] as $serverName => $serverConfig) {
            $this->servers[$serverName] = new Server($serverName, $serverConfig);
        }

        foreach ($ideConfig['deployment'] as $deploymentName => $deploymentConfig) {
            $this->deployments[$deploymentName] = new Deployment($deploymentName, $deploymentConfig);
        }

        if (!empty($ideConfig['php'])) {
            $this->php = new PHP($ideConfig['php']);
        }

        if (!empty($ideConfig['javascript'])) {
            $this->javascript = new JavaScript($ideConfig['javascript']);
        }

        if (!empty($ideConfig['vcs'])) {
            $this->vcs = new VCS($ideConfig['vcs']);
        }

        foreach ($ideConfig['databases'] as $databaseName => $databaseConfig) {
            $this->databases[$databaseName] = new Database($databaseName, $databaseConfig);
        }

        if (!empty($ideConfig['sql'])) {
            $this->sqlDialects = new SQLDialects($ideConfig['sql']);
        }

        if (null !== $ideConfig['symfony']) {
            $this->symfony = new Symfony($ideConfig['symfony']);
        }

        if (null !== $ideConfig['laravel']) {
            $this->laravel = new Laravel($ideConfig['laravel']);
        }

        foreach ($ideConfig['run'] as $runName => $runConfig) {
            $this->runs[$runName] = new Run($runName, $runConfig);
        }
    }

    private function getEnvironmentVariable(string $environmentVariable): string
    {
        if (isset($this->knownEnvironmentVariables[$environmentVariable])) {
            return $this->knownEnvironmentVariables[$environmentVariable];
        }

        $value = getenv($environmentVariable);

        if (false === $value) {
            throw new \RuntimeException(
                sprintf(
                    'On ide-config.yaml you are making use of an environment variable, %s, but is not set',
                    $environmentVariable,
                )
            );
        }

        return $this->knownEnvironmentVariables[$environmentVariable] = $value;
    }

    private function replaceEnvironmentVariablesInString(string $string): string
    {
        $numberMatches = preg_match_all('/env\((?\'var\'\w+?)\)/', $string, $matches);

        if (false === $numberMatches) {
            throw new \RuntimeException('Something went wrong when replacing the environment variables');
        }

        if (0 < $numberMatches) {
            foreach (array_unique($matches['var']) as $environmentVariable) {
                $string = str_replace(
                    'env(' . $environmentVariable . ')',
                    $this->getEnvironmentVariable($environmentVariable),
                    $string
                );
            }
        }

        return $string;
    }

    private function resolveDependencies(array $configArray): array
    {
        $resolved = [];

        foreach ($configArray as $key => $value) {
            $newKey = is_string($key)
                ? $this->replaceEnvironmentVariablesInString($key)
                : $key;

            if (is_array($value)) {
                $resolved[$newKey] = $this->resolveDependencies($value);
            } elseif (is_string($value)) {
                $resolved[$newKey] = $this->replaceEnvironmentVariablesInString($value);
            } else {
                $resolved[$newKey] = $value;
            }
        }

        return $resolved;
    }

    private function configureTopLevelOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('modules');

        $resolver->setDefaults(
            [
                'composer' => [],
                'servers' => [],
                'deployment' => [],
                'php' => [],
                'javascript' => [],
                'vcs' => [],
                'databases' => [],
                'sql' => [],
                'symfony' => null,
                'laravel' => null,
                'run' => [],
            ]
        );

        $resolver->setAllowedTypes('modules', 'array');
        $resolver->setAllowedTypes('composer', 'array');
        $resolver->setAllowedTypes('servers', 'array');
        $resolver->setAllowedTypes('deployment', 'array');
        $resolver->setAllowedTypes('php', 'array');
        $resolver->setAllowedTypes('javascript', 'array');
        $resolver->setAllowedTypes('vcs', 'array');
        $resolver->setAllowedTypes('databases', 'array');
        $resolver->setAllowedTypes('sql', 'array');
        $resolver->setAllowedTypes('symfony', ['null', 'array']);
        $resolver->setAllowedTypes('laravel', ['null', 'array']);
        $resolver->setAllowedTypes('run', 'array');
    }

    public function getModules(): ModuleCollection
    {
        return $this->modules;
    }

    public function getModule(string $name): Module
    {
        return $this->modules[$name];
    }

    public function getComposer(): ?Composer
    {
        return $this->composer;
    }

    public function getServers(): ServerCollection
    {
        return $this->servers;
    }

    public function getPHP(): ?PHP
    {
        return $this->php;
    }

    public function getJavaScript(): ?JavaScript
    {
        return $this->javascript;
    }

    public function getDeployments(): DeploymentCollection
    {
        return $this->deployments;
    }

    public function getVCS(): ?VCS
    {
        return $this->vcs;
    }

    public function getDatabases(): DatabaseCollection
    {
        return $this->databases;
    }

    public function getSQLDialects(): ?SQLDialects
    {
        return $this->sqlDialects;
    }

    public function getSymfony(): ?Symfony
    {
        return $this->symfony;
    }

    public function getLaravel(): ?Laravel
    {
        return $this->laravel;
    }

    public function getRuns(): RunCollection
    {
        return $this->runs;
    }
}
