<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Util;

class ComposerLockHelper
{
    private $contents = [];

    public function __construct(string $projectRootDir)
    {
        $composerLockPath = $projectRootDir . '/composer.lock';

        if (!file_exists($composerLockPath)) {
            return ;
        }

        $this->contents = json_decode(file_get_contents($composerLockPath), true);
    }

    public function hasPackage(string $packageName): bool
    {
        foreach (array_merge($this->contents['packages'] ?? [], $this->contents['packages-dev'] ?? []) as $package) {
            if ($package['name'] === $packageName) {
                return true;
            }
        }

        return false;
    }

    public function getPackageVersion(string $packageName): string
    {
        foreach (array_merge($this->contents['packages'] ?? [], $this->contents['packages-dev'] ?? []) as $package) {
            if ($package['name'] === $packageName) {
                $version = $package['version'];

                if ('v' === ($firstCharacter = substr($version, 0, 1))) {
                    return substr($version, 1);
                }

                return is_numeric($firstCharacter) ? $version : '';
            }
        }

        return '';
    }
}
