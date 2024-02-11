<?php

declare(strict_types=1);

namespace ScrumWorks\EnumMapping;

use InvalidArgumentException;
use UnitEnum;

/**
 * @template TUnitEnum of UnitEnum
 * @template TMappingValue of int|string
 */
final class EnumMappingMemoryStorage
{
    /**
     * @var array<string, TMappingValue>
     */
    private array $fromEnum = [];

    /**
     * @var array<TMappingValue, TUnitEnum>
     */
    private array $toEnum = [];

    /**
     * @param class-string<TUnitEnum> $enumClassName
     */
    public function __construct(private readonly string $enumClassName)
    {
    }

    /**
     * @param TUnitEnum $enum
     */
    public function enumMappingExists(UnitEnum $enum): bool
    {
        \assert($enum::class === $this->enumClassName);

        return isset($this->fromEnum[$this->enumToString($enum)]);
    }

    /**
     * @param TUnitEnum $enum
     * @return TMappingValue
     */
    public function enumToMappingValue(UnitEnum $enum): mixed
    {
        \assert($enum::class === $this->enumClassName);

        $enumString = $this->enumToString($enum);
        return $this->fromEnum[$enumString]
            ?? throw new InvalidArgumentException("There is no mapping for enum `{$enumString}`.");
    }

    /**
     * @param TMappingValue $mappingValue
     */
    public function mappingValueExists(mixed $mappingValue): bool
    {
        return isset($this->toEnum[$mappingValue]);
    }

    /**
     * @param TMappingValue $mappingValue
     * @return TUnitEnum
     */
    public function mappingValueToEnum(mixed $mappingValue): UnitEnum
    {
        return $this->toEnum[$mappingValue]
            ?? throw new InvalidArgumentException("There is no mapping for value `{$mappingValue}`.");
    }

    /**
     * @return TMappingValue[]
     */
    public function getAllMappingValues(): array
    {
        return \array_values($this->fromEnum);
    }

    /**
     * @param TUnitEnum $enum
     * @param TMappingValue $mappingValue
     */
    public function storeMapping(UnitEnum $enum, mixed $mappingValue): void
    {
        \assert($enum::class === $this->enumClassName);
        $enumString = $this->enumToString($enum);

        if (
            isset($this->fromEnum[$enumString]) && $this->fromEnum[$enumString] !== $mappingValue
            || isset($this->toEnum[$mappingValue]) && $this->toEnum[$mappingValue] !== $enum
        ) {
            throw new InvalidArgumentException("Enum `{$enumString}` already has different mapping value.");
        }

        $this->fromEnum[$enumString] = $mappingValue;
        $this->toEnum[$mappingValue] = $enum;
    }

    private function enumToString(UnitEnum $enum): string
    {
        return $enum::class . '::' . $enum->name;
    }
}
