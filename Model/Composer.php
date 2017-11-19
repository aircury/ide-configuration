<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Composer
{
    /**
     * @var OptionsResolver
     */
    private static $optionResolver;

    /**
     * @var bool
     */
    private $ask;

    /**
     * @var bool
     */
    private $synchronize;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options           = self::$optionResolver->resolve($options);
        $this->ask         = $options['ask'];
        $this->synchronize = $options['synchronize'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('ask', true);
        $resolver->setAllowedTypes('ask', 'bool');

        $resolver->setDefault('synchronize', false);
        $resolver->setAllowedTypes('ask', 'bool');
    }

    public function getAsk(): bool
    {
        return $this->ask;
    }

    public function getSynchronize(): bool
    {
        return $this->synchronize;
    }
}
