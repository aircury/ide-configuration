<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\IDEConfiguration;
use Aircury\IDEConfiguration\Model\Behat;
use Aircury\IDEConfiguration\Model\Interpreter;
use Aircury\IDEConfiguration\Model\PHPUnit;
use Aircury\IDEConfiguration\Util\ComposerLockHelper;
use Aircury\Xml\Node;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webpatser\Uuid\Uuid;

class PHPManipulator
{
    public function addPHP(Node $php, IDEConfiguration $configuration, string $projectsRemoteRootDir): void
    {
        $phpConfiguration = $configuration->getPHP();

        if (null === ($languageLevel = $phpConfiguration->getLanguageLevel())) {
            $php->getNamedChild(
                'component',
                ['name' => 'PhpProjectSharedConfiguration']
            )['php_language_level'] = $languageLevel;
        }

        $additionalPHPIncludePaths = [];

        foreach ($configuration->getModules()->toArray() as $module) {
            foreach ($module->getLibraries() as $libraryFolder) {
                $additionalPHPIncludePaths[] = $libraryFolder;
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

        $interpretersConfiguration = $phpConfiguration->getInterpreters();

        if (!$interpretersConfiguration->isEmpty()) {
            $interpreters = $php
                ->getNamedChild('component', ['name' => 'PhpInterpreters'])
                ->getNamedChild('interpreters');

            foreach ($interpretersConfiguration->toArray() as $interpreterName => $interpreterSettings) {
                $interpreter = $interpreters->getNamedChild('interpreter', ['name' => $interpreterName]);

                $interpreter['id'] = $interpreter['id'] ?? Uuid::generate(4)->string;

                $interpreterSettings->setId($interpreter['id']);

                $interpreter['debugger_id'] = 'php.debugger.XDebug';

                if ('SSH' === $interpreterSettings->getType()) {
                    $interpreter['home'] = sprintf(
                        'ssh://%s@%s:%s%s',
                        $interpreterSettings->getUsername(),
                        $interpreterSettings->getHost(),
                        $interpreterSettings->getPort(),
                        $interpreterSettings->getPHPPath()
                    );
                }

                $remoteData = $interpreter->getNamedChild('remote_data');

                $remoteData['HOST'] = $interpreterSettings->getHost();
                $remoteData['PORT'] = $interpreterSettings->getPort();
                $remoteData['USERNAME'] = $interpreterSettings->getUsername();
                $remoteData['PRIVATE_KEY_FILE'] = $interpreterSettings->getPrivateKey();
                $remoteData['MY_KNOWN_HOSTS_FILE'] = '';
                $remoteData['USE_KEY_PAIR'] = 'true';
                $remoteData['USE_AUTH_AGENT'] = 'false';
                $remoteData['INTERPRETER_PATH'] = $interpreterSettings->getPHPPath();
                $remoteData['HELPERS_PATH'] = $projectsRemoteRootDir . '/.phpstorm_helpers';
                $remoteData['INITIALIZED'] = 'false';
                $remoteData['VALID'] = 'true';
            }
        }
    }

    public function addBehat(
        Node $php,
        Behat $behatConfiguration,
        Interpreter $interpreter,
        string $projectRemoteRootDir
    ): void {
        $behat = $php
            ->getNamedChild('component', ['name' => 'Behat'])
            ->getNamedChild('behat_settings')
            ->getNamedChild('behat_by_interpreter', ['interpreter_id' => $interpreter->getId()]);

        $behat['configuration_file_path'] = $projectRemoteRootDir . '/' . $behatConfiguration->getConfiguration();
        $behat['behat_path'] = $projectRemoteRootDir . '/' . $behatConfiguration->getBinPath();
        $behat['use_configuration_file'] = 'true';
    }

    public function addPHPUnit(
        Node $php,
        PHPUnit $phpunitConfiguration,
        Interpreter $interpreter,
        string $projectRootDir,
        string $projectRemoteRootDir
    ): void {
        $phpunit = $php
            ->getNamedChild('component', ['name' => 'PhpUnit'])
            ->getNamedChild('phpunit_settings')
            ->getNamedChild('phpunit_by_interpreter', ['interpreter_id' => $interpreter->getId()]);

        $phpunit['load_method'] = 'CUSTOM_LOADER';
        $phpunit['configuration_file_path'] = $projectRemoteRootDir . '/' . $phpunitConfiguration->getConfiguration();

        if ('auto' === ($loader = $phpunitConfiguration->getLoader())) {
            $composerLockHelper = new ComposerLockHelper($projectRootDir);

            if ($composerLockHelper->hasPackage('phpunit/phpunit')) {
                $loader = 'vendor/autoload.php';
            } elseif ($composerLockHelper->hasPackage('symfony/phpunit-bridge')) {
                $phpUnitPath = $projectRootDir . '/vendor/symfony/phpunit-bridge/bin/simple-phpunit';

                if (!file_exists($phpUnitPath)) {
                    throw new \RuntimeException(
                        'Cannot find simple PHPUnit. Have you done "composer install" on this project?'
                    );
                }

                $phpUnit = new Process($phpUnitPath . ' --version');

                $phpUnit->run();

                $phpUnitVersion = implode('.', array_slice(explode('.', explode(' ', $phpUnit->getOutput())[1]), 0, 2));
                $loaderDir = 'vendor/bin/.phpunit/phpunit-' . $phpUnitVersion;
                $loader = $loaderDir . '/vendor/autoload.php';

                // PHPStorm needs this file to exist to launch, so just symlink it

                if (!file_exists($projectRootDir . '/' . $loaderDir . '/vendor/phpunit/phpunit/phpunit')) {
                    $filesystem = new Filesystem();

                    $filesystem->mkdir($projectRootDir . '/' . $loaderDir . '/vendor/phpunit/phpunit');
                    $filesystem->symlink(
                        $projectRootDir . '/' . $loaderDir . '/phpunit',
                        $projectRootDir . '/' . $loaderDir . '/vendor/phpunit/phpunit/phpunit'
                    );
                }
            } else {
                throw new \RuntimeException(
                    'Set to figure out the phpunit loader automatically but was unable to do so'
                );
            }
        }

        $phpunit['custom_loader_path'] = $projectRemoteRootDir . '/' . $loader;
        $phpunit['phpunit_phar_path'] = '';
        $phpunit['use_configuration_file'] = 'true';
    }
}
