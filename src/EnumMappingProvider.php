<?php

namespace ScrumWorks\EnumMapping;

use UnitEnum;

/**
 * @template TEnumMapping of AbstractEnumMapping
 * @template TUnitEnum of UnitEnum
 */
final readonly class EnumMappingProvider
{
    /**
     * @var array<class-string<TEnumMapping<TUnitEnum>>, TEnumMapping<TUnitEnum>>
     */
    private array $enumMappings;

    /**
     * @param array<TEnumMapping<TUnitEnum>> $enumMappings
     */
    public function __construct(array $enumMappings)
    {
        $this->enumMappings = \array_combine(
            \array_map(fn (AbstractEnumMapping $mapping): string => $mapping::class, $enumMappings),
            $enumMappings
        );
    }

    /**
     * @param class-string<TEnumMapping<TUnitEnum>> $className
     *
     * @return TEnumMapping<TUnitEnum>
     */
    public function get(string $className): AbstractEnumMapping
    {
        return $this->enumMappings[$className];
    }
}
