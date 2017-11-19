<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Symfony
{
    /**
     * @var OptionsResolver
     */
    private static $optionResolver;

    /**
     * @var string
     */
    private $web;

    /**
     * @var string
     */
    private $app;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options   = self::$optionResolver->resolve($options);
        $this->web = $options['web'];
        $this->app = $options['app'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('web', null);
        $resolver->setAllowedTypes('web', ['string', 'null']);

        $resolver->setDefault('app', null);
        $resolver->setAllowedTypes('app', ['string', 'null']);
    }

    public function getWeb(): ?string
    {
        return $this->web;
    }

    public function getApp(): ?string
    {
        return $this->app;
    }
}
