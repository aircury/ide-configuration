<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class JavaScript
{
    /**
     * @var OptionsResolver[]
     */
    private static $optionResolver;

    /**
     * @var string|null
     */
    private $languageLevel;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options = self::$optionResolver->resolve($options);
        $this->languageLevel = $options['language_level'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('language_level', null);
        $resolver->setAllowedTypes('language_level', ['string', 'null']);
    }

    public function getLanguageLevel(): ?string
    {
        return $this->languageLevel;
    }
}
