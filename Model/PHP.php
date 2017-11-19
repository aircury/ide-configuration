<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class PHP
{
    /**
     * @var OptionsResolver[]
     */
    private static $optionResolver;

    /**
     * @var float|null
     */
    private $languageLevel;

    /**
     * @var Xdebug|null
     */
    private $xdebug;

    /**
     * @var InterpreterCollection
     */
    private $interpreters;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options             = self::$optionResolver->resolve($options);
        $this->languageLevel = $options['language_level'];
        $this->interpreters  = new InterpreterCollection();

        if (!empty($options['xdebug'])) {
            $this->xdebug = new Xdebug($options['xdebug']);
        }

        foreach ($options['interpreters'] as $interpreterName => $interpreterOptions) {
            $this->interpreters[$interpreterName] = new Interpreter($interpreterName, $interpreterOptions);
        }
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('language_level', null);
        $resolver->setAllowedTypes('language_level', ['float', 'null']);

        $resolver->setDefault('xdebug', []);
        $resolver->setAllowedTypes('xdebug', 'array');

        $resolver->setDefault('interpreters', []);
        $resolver->setAllowedTypes('interpreters', 'array');
    }

    public function getLanguageLevel(): ?float
    {
        return $this->languageLevel;
    }

    public function getXDebug(): ?Xdebug
    {
        return $this->xdebug;
    }

    public function getInterpreters(): InterpreterCollection
    {
        return $this->interpreters;
    }
}
