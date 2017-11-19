<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Deployment
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
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string|null
     */
    private $privateKey;

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

        $this->name       = $name;
        $options          = self::$optionResolver->resolve($options);
        $this->type       = $options['type'];
        $this->host       = $options['host'];
        $this->url        = $options['url'];
        $this->port       = strval($options['port']);
        $this->privateKey = $options['private_key'];
        $this->mappings   = $options['mappings'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('type');
        $resolver->setAllowedTypes('type', 'string');

        $resolver->setRequired('host');
        $resolver->setAllowedTypes('host', 'string');

        $resolver->setRequired('url');
        $resolver->setAllowedTypes('url', 'string');

        $resolver->setRequired('port');
        $resolver->setAllowedTypes('port', ['int', 'string']);

        $resolver->setDefault('private_key', null);
        $resolver->setAllowedTypes('private_key', ['null', 'string']);

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
                                'All the mappings inside "deployment" should be string => string. %s => %s found',
                                gettype($key),
                                gettype($value)
                            )
                        );
                    }
                }

                return $mappings;
            }
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    /**
     * @return string[]
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }
}
