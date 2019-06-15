<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Database
{
    /**
     * @var OptionsResolver
     */
    private static $optionResolver;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $driver;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string[]
     */
    private $schemas;

    /**
     * @var string|null
     */
    private $color;

    /**
     * @var string
     */
    private $id;

    public function __construct(string $name, array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $this->name = $name;
        $options = self::$optionResolver->resolve($options);
        $this->driver = $options['driver'];
        $this->host = $options['host'];
        $this->port = (string) $options['port'];
        $this->database = $options['database'];
        $this->username = $options['username'];
        $this->path = $options['path'];
        $this->schemas = $options['schemas'];
        $this->color = $options['color'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('driver');
        $resolver->setAllowedTypes('driver', 'string');

        $resolver->setDefault('host', null);
        $resolver->setAllowedTypes('host', ['null', 'string']);

        $resolver->setDefault('port', null);
        $resolver->setAllowedTypes('port', ['null', 'int', 'string']);

        $resolver->setDefault('database', null);
        $resolver->setAllowedTypes('database', ['null', 'string']);

        $resolver->setDefault('username', null);
        $resolver->setAllowedTypes('username', ['null', 'string']);

        $resolver->setDefault('path', null);
        $resolver->setAllowedTypes('path', ['null', 'string']);
        $resolver->setNormalizer(
            'path',
            function (Options $options, $path) {
                if (
                    null === $path &&
                    (
                        null === $options['host'] ||
                        null === $options['port'] ||
                        null === $options['database'] ||
                        null === $options['username']
                    )
                ) {
                    throw new \InvalidArgumentException(
                        'When path is null, then host, port, database and username must be provided'
                    );
                }

                return $path;
            }
        );

        $resolver->setDefault('schemas', []);
        $resolver->setAllowedTypes('schemas', 'array');
        $resolver->setNormalizer(
            'schemas',
            function (Options $options, $mappings) {
                $options->count();

                foreach ($mappings as $value) {
                    if (!is_string($value)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'All the schemas inside "databases" should be strings. %s found',
                                gettype($value)
                            )
                        );
                    }
                }

                return $mappings;
            }
        );

        $resolver->setDefault('color', null);
        $resolver->setAllowedTypes('color', ['null', 'string']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function getPort(): ?string
    {
        return $this->port;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @return string[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
