<?php

namespace ScrumWorks\EnumMapping;

use UnitEnum;

final readonly class EnumMappingProvider
{
    /**
     * @var array<class-string<AbstractEnumMapping>, AbstractEnumMapping>
     */
    private array $enumMappings;

    /**
     * @param array<AbstractEnumMapping> $enumMappings
     */
    public function __construct(array $enumMappings)
    {
        $this->enumMappings = \array_combine(
            \array_map(fn (AbstractEnumMapping $mapping): string => $mapping::class, $enumMappings),
            $enumMappings
        );
    }

    /**
     * @template TEnumMapping of AbstractEnumMapping
     * @param class-string<TEnumMapping> $className
     *
     * @return TEnumMapping
     */
    public function get(string $className): ?AbstractEnumMapping
    {
        if (! \array_key_exists($className, $this->enumMappings)) {
            return null;
        }
        $instance = $this->enumMappings[$className];
        \assert($instance instanceof $className);

        return $instance;
    }
}
