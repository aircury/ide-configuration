<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Module
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
    private $root;

    /**
     * @var string[]
     */
    private $excluded;

    /**
     * @var string[]
     */
    private $sources;

    /**
     * @var string[]
     */
    private $tests;

    /**
     * @var string[]
     */
    private $libraries;

    /**
     * @var string[]
     */
    private $resources;

    public function __construct(string $name, array $options)
    {
        if (null === self::$optionResolver) {
            self::$optionResolver = new OptionsResolver();

            $this->configureOptions(self::$optionResolver);
        }

        $this->name      = $name;
        $options         = self::$optionResolver->resolve($options);
        $this->root      = $options['root'];
        $this->excluded  = $options['excluded'];
        $this->sources   = $options['sources'];
        $this->tests     = $options['tests'];
        $this->libraries = $options['libraries'];
        $this->resources = $options['resources'];
    }

    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('root');
        $resolver->setAllowedTypes('root', 'string');

        foreach (['excluded', 'sources', 'tests', 'libraries', 'resources'] as $folders) {
            $resolver->setDefault($folders, []);
            $resolver->setAllowedTypes($folders, 'array');
            $resolver->setNormalizer(
                $folders,
                function (Options $options, $values) use ($folders) {
                    $options->count();

                    foreach ($values as $value) {
                        if (!is_string($value)) {
                            throw new \InvalidArgumentException(
                                sprintf(
                                    'All the %s folders inside "modules" should be strings. %s found',
                                    $folders,
                                    gettype($value)
                                )
                            );
                        }
                    }

                    return $values;
                }
            );
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @return string[]
     */
    public function getExcluded(): array
    {
        return $this->excluded;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @return string[]
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * @return string[]
     */
    public function getLibraries(): array
    {
        return $this->libraries;
    }

    public function hasResources(): bool
    {
        return !empty($this->resources);
    }

    public function getResources(): array
    {
        return $this->resources;
    }
}
