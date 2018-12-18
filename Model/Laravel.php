<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Laravel
{
    /**
     * @var OptionsResolver
     */
    private static $optionResolver;

    /**
     * @var bool
     */
    private $pluginEnabled;

    /**
     * @var string
     */
    private $routerNamespace;

    /**
     * @var string
     */
    private $mainLanguage;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options = self::$optionResolver->resolve($options);
        $this->pluginEnabled = $options['pluginEnabled'];
        $this->routerNamespace = $options['routerNamespace'];
        $this->mainLanguage = $options['mainLanguage'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('pluginEnabled', true);
        $resolver->setAllowedTypes('pluginEnabled', ['bool', 'null']);

        $resolver->setDefault('routerNamespace', null);
        $resolver->setAllowedTypes('routerNamespace', ['string', 'null']);

        $resolver->setDefault('mainLanguage', 'en');
        $resolver->setAllowedTypes('mainLanguage', ['string', 'null']);
    }

    public function getPluginEnabled(): ?bool
    {
        return $this->pluginEnabled;
    }

    public function getRouterNamespace(): ?string
    {
        return $this->routerNamespace;
    }

    public function getMainLanguage(): ?string
    {
        return $this->mainLanguage;
    }
}
