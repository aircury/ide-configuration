<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\VCS;
use Aircury\Xml\Node;

class VCSManipulator
{
    public function addVCS(Node $vcs, VCS $vcsConfiguration): void
    {
        $vcsDirectoryMappings = $vcs->getNamedChild('component', ['name' => 'VcsDirectoryMappings']);

        foreach ($vcsConfiguration->getMappings() as $directory => $vcsSystem) {
            $vcsDirectoryMappings->getNamedChild('mapping', ['directory' => $directory])['vcs'] = $vcsSystem;
        }
    }
}
