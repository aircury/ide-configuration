<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Xdebug
{
    /**
     * @var OptionsResolver
     */
    private static $optionResolver;

    /**
     * @var int
     */
    private $port;

    public function __construct(array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $options = self::$optionResolver->resolve($options);
        $this->port = null === $options['port'] ? null : intval($options['port']);
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('port', null);
        $resolver->setAllowedTypes('port', ['integer', 'string', 'null']);
    }

    public function getPort()
    {
        return $this->port;
    }
}
