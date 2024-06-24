<?php

declare(strict_types=1);

namespace ScrumWorks\EnumMapping;

use InvalidArgumentException;
use UnitEnum;

final class EnumMappingMemoryStorage
{
    /**
     * @var array<string, int|string>
     */
    private array $fromEnum = [];

    /**
     * @var array<int|string, UnitEnum>
     */
    private array $toEnum = [];

    /**
     * @param class-string<UnitEnum> $enumClassName
     */
    public function __construct(public readonly string $enumClassName)
    {
    }

    public function enumMappingExists(UnitEnum $enum): bool
    {
        \assert($enum::class === $this->enumClassName);

        return isset($this->fromEnum[$this->enumToString($enum)]);
    }

    /**
     * @return int|string
     */
    public function enumToMappingValue(UnitEnum $enum): mixed
    {
        \assert($enum::class === $this->enumClassName);

        $enumString = $this->enumToString($enum);
        return $this->fromEnum[$enumString]
            ?? throw new InvalidArgumentException("There is no mapping for enum `{$enumString}`.");
    }

    /**
     * @param int|string $mappingValue
     */
    public function mappingValueExists(mixed $mappingValue): bool
    {
        return isset($this->toEnum[$mappingValue]);
    }

    /**
     * @param int|string $mappingValue
     */
    public function mappingValueToEnum(mixed $mappingValue): UnitEnum
    {
        return $this->toEnum[$mappingValue]
            ?? throw new InvalidArgumentException("There is no mapping for value `{$mappingValue}`.");
    }

    /**
     * @return array<int|string>
     */
    public function getAllMappingValues(): array
    {
        return \array_values($this->fromEnum);
    }

    /**
     * @param int|string $mappingValue
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
