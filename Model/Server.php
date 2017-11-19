<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Server
{
    /**
     * @var OptionsResolver[]
     */
    private static $optionResolver;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $host;

    /**
     * @var string[]
     */
    private $mappings;

    public function __construct(string $name, array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $this->name     = $name;
        $options        = self::$optionResolver->resolve($options);
        $this->host     = $options['host'];
        $this->mappings = $options['mappings'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('host', null);
        $resolver->setAllowedTypes('host', ['string', 'null']);

        $resolver->setDefault('mappings', []);
        $resolver->setAllowedTypes('mappings', 'array');
        $resolver->setNormalizer(
            'mappings',
            function (Options $options, $mappings) {
                $options->count();

                foreach ($mappings as $key => $value) {
                    if (!is_string($key) || !is_string($value)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'All the mappings inside "servers" should be strings. %s found',
                                gettype($value)
                            )
                        );
                    }
                }

                return $mappings;
            }
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return string[]
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }
}
