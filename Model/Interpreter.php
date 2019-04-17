<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Interpreter
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
    private $type;

    /**
     * @var string
     */
    private $username;

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
    private $phpPath;

    /**
     * @var string|null
     */
    private $privateKey;

    /**
     * @var PHPUnit|null
     */
    private $phpUnit;

    /**
     * @var Behat|null
     */
    private $behat;

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
        $this->type = $options['type'];
        $this->username = $options['username'];
        $this->host = $options['host'];
        $this->port = strval($options['port']);
        $this->phpPath = $options['php_path'];
        $this->privateKey = $options['private_key'];

        if (!empty($options['phpunit'])) {
            $this->phpUnit = new PHPUnit($options['phpunit']);
        }

        if (!empty($options['behat'])) {
            $this->behat = new Behat($options['behat']);
        }
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('type', null);
        $resolver->setAllowedTypes('type', ['string', 'null']);

        $resolver->setRequired('username');
        $resolver->setAllowedTypes('username', 'string');

        $resolver->setRequired('host');
        $resolver->setAllowedTypes('host', 'string');

        $resolver->setRequired('port');
        $resolver->setAllowedTypes('port', ['integer', 'string']);

        $resolver->setRequired('php_path');
        $resolver->setAllowedTypes('php_path', 'string');

        $resolver->setDefault('private_key', null);
        $resolver->setAllowedTypes('private_key', ['null', 'string']);

        $resolver->setDefault('phpunit', []);
        $resolver->setAllowedTypes('phpunit', 'array');

        $resolver->setDefault('behat', []);
        $resolver->setAllowedTypes('behat', 'array');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): string
    {
        return $this->port;
    }

    public function getPHPPath(): string
    {
        return $this->phpPath;
    }

    public function getPrivateKey(): ?string
    {
        return $this->privateKey;
    }

    public function getPHPUnit(): ?PHPUnit
    {
        return $this->phpUnit;
    }

    public function getBehat(): ?Behat
    {
        return $this->behat;
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
