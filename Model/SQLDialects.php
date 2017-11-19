<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Model;

class SQLDialects
{
    /**
     * @var string[]
     */
    private $mappings;

    public function __construct(array $mappings)
    {
        foreach ($mappings as $sourceDir => $targetDir) {
            if (!is_string($sourceDir) || !is_string($targetDir)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'All the %s elements inside "sql" should be string => string. %s => %s found',
                        gettype($sourceDir),
                        gettype($targetDir)
                    )
                );
            }
        }

        $this->mappings = $mappings;
    }

    /**
     * @return string[]
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }
}
