<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\Interpreter;
use Aircury\Xml\Node;

class PHPTestFrameworkManipulator
{
    public function addBehat(Node $phpTestFramework, Interpreter $interpreter, string $projectRootDir): void
    {
        $toolsCache = $phpTestFramework
            ->getNamedChild('component', ['name' => 'PhpTestFrameworkVersionCache'])
            ->getNamedChild('tools_cache');

        $toolsCache
            ->getNamedChild('tool', ['tool_name' => 'Behat'])
            ->getNamedChild('cache')
            ->getNamedChild('versions')
            ->getNamedChild('info', ['id' => 'interpreter-' . $interpreter->getId()])
        ['version'] = $this->getPackageVersion($projectRootDir, 'behat/behat');
    }

    public function addPHPUnit(Node $phpTestFramework, Interpreter $interpreter, string $projectRootDir): void
    {
        $toolsCache = $phpTestFramework
            ->getNamedChild('component', ['name' => 'PhpTestFrameworkVersionCache'])
            ->getNamedChild('tools_cache');

        $toolsCache
            ->getNamedChild('tool', ['tool_name' => 'PHPUnit'])
            ->getNamedChild('cache')
            ->getNamedChild('versions')
            ->getNamedChild('info', ['id' => 'interpreter-' . $interpreter->getId()])
        ['version'] = $this->getPackageVersion($projectRootDir, 'phpunit/phpunit');
    }

    private function getPackageVersion(string $projectRootDir, string $packageName): string
    {
        $composerLockPath = $projectRootDir . '/composer.lock';

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
}
