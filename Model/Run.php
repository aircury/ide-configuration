<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Run
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
     * @var string|null
     */
    private $folder;

    /**
     * @var string|null
     */
    private $parameters;

    /**
     * @var string|null
     */
    private $script;

    /**
     * @var string[]
     */
    private $environment;

    /**
     * @var string|null
     */
    private $server;

    /**
     * @var string|null
     */
    private $url;

    public function __construct(string $name, array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $this->name = $name;
        $options = self::$optionResolver->resolve($options);
        $this->type = $options['type'];
        $this->folder = $options['folder'];
        $this->parameters = $options['parameters'];
        $this->script = $options['script'];
        $this->environment = $options['environment'];
        $this->server = $options['server'];
        $this->url = $options['url'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('type');
        $resolver->setAllowedTypes('type', 'string');

        $resolver->setDefault('folder', null);
        $resolver->setAllowedTypes('folder', ['string', 'null']);

        $resolver->setDefault('parameters', null);
        $resolver->setAllowedTypes('parameters', ['string', 'null']);

        $resolver->setDefault('script', null);
        $resolver->setAllowedTypes('script', ['string', 'null']);

        $resolver->setDefault('environment', []);
        $resolver->setAllowedTypes('environment', 'array');
        $resolver->setNormalizer(
            'environment',
            function (Options $options, $environment) {
                $options->count();

                foreach ($environment as $key => $value) {
                    if (!is_string($key) || !is_string($value)) {
                        throw new \InvalidArgumentException(
                            sprintf(
                                'All the environment inside "deployment" should be string => string. %s => %s found',
                                gettype($key),
                                gettype($value)
                            )
                        );
                    }
                }

                return $environment;
            }
        );

        $resolver->setDefault('server', null);
        $resolver->setAllowedTypes('server', ['string', 'null']);

        $resolver->setDefault('url', null);
        $resolver->setAllowedTypes('url', ['string', 'null']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFolder(): ?string
    {
        return $this->folder;
    }

    public function getParameters(): ?string
    {
        return $this->parameters;
    }

    public function getScript(): ?string
    {
        return $this->script;
    }

    /**
     * @return string[]
     */
    public function getEnvironment(): array
    {
        return $this->environment;
    }

    public function getServer(): ?string
    {
        return $this->server;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
