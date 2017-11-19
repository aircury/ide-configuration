<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class PHPUnit
{
    /**
     * @var OptionsResolver
     */
    private static $optionResolver;

    /**
     * @var string
     */
    private $configuration;

    /**
     * @var string
     */
    private $loader;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options             = self::$optionResolver->resolve($options);
        $this->configuration = $options['configuration'];
        $this->loader        = $options['loader'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('configuration');
        $resolver->setAllowedTypes('configuration', 'string');

        $resolver->setRequired('loader');
        $resolver->setAllowedTypes('loader', 'string');
    }

    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    public function getLoader(): string
    {
        return $this->loader;
    }
}
