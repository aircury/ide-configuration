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
     * @var string[]
     */
    private $schemas;

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

        $this->name     = $name;
        $options        = self::$optionResolver->resolve($options);
        $this->driver   = $options['driver'];
        $this->host     = $options['host'];
        $this->port     = strval($options['port']);
        $this->database = $options['database'];
        $this->username = $options['username'];
        $this->schemas  = $options['schemas'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('driver');
        $resolver->setAllowedTypes('driver', 'string');

        $resolver->setRequired('host');
        $resolver->setAllowedTypes('host', 'string');

        $resolver->setRequired('port');
        $resolver->setAllowedTypes('port', ['int', 'string']);

        $resolver->setRequired('database');
        $resolver->setAllowedTypes('database', 'string');

        $resolver->setRequired('username');
        $resolver->setAllowedTypes('username', 'string');

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
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getUsername(): string
    {
        return $this->username;
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
