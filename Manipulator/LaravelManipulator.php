<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\Laravel;
use Aircury\Xml\Node;

class LaravelManipulator
{
    public function addLaravel(Node $laravel, Laravel $laravelConfiguration): void
    {
        $laravelPlugin = $laravel->getNamedChild('component', ['name' => 'LaravelPluginSettings']);

        $laravelPlugin->getNamedChild('option', ['name' => 'pluginEnabled'])['value'] = 'true';

        if (null !== ($routerNamespace = $laravelConfiguration->getRouterNamespace())) {
            $laravelPlugin->getNamedChild('option', ['name' => 'routerNamespace'])['value'] = $routerNamespace;
        }

        if (null !== ($mainLanguage = $laravelConfiguration->getMainLanguage())) {
            $laravelPlugin->getNamedChild('option', ['name' => 'mainLanguage'])['value'] = $mainLanguage;
        }
    }
}
