<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Behat
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
    private $binPath;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options             = self::$optionResolver->resolve($options);
        $this->configuration = $options['configuration'];
        $this->binPath       = $options['bin_path'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('configuration');
        $resolver->setAllowedTypes('configuration', 'string');

        $resolver->setRequired('bin_path');
        $resolver->setAllowedTypes('bin_path', 'string');
    }

    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    public function getBinPath(): string
    {
        return $this->binPath;
    }
}
