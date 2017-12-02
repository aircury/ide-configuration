<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\JavaScript;
use Aircury\Xml\Node;

class MiscManipulator
{
    public function addJavaScript(Node $misc, JavaScript $javascriptConfiguration): void
    {
        if (null !== ($languageLevel = $javascriptConfiguration->getLanguageLevel())) {
            $javascriptSettings = $misc->getNamedChild('component', ['name' => 'JavaScriptSettings']);

            $javascriptSettings->getNamedChild('option', ['name' => 'languageLevel'])['value'] = $languageLevel;
        }
    }
}
