<?php

declare(strict_types=1);

namespace ScrumWorks\EnumMapping;

use BackedEnum;
use InvalidArgumentException;
use LogicException;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use ScrumWorks\EnumMapping\Exception\UnexpectedEnumMappingValueException;
use ScrumWorks\EnumMapping\Strategy\EnumMappingSame;
use ScrumWorks\EnumMapping\Strategy\EnumMappingValue;
use UnitEnum;

abstract class AbstractEnumMapping
{
    /**
     * @var array<class-string<UnitEnum>, EnumMappingMemoryStorage>
     */
    private array $mappings = [];

    public function enumMappingExists(UnitEnum $enum): bool
    {
        return $this->getMappingStorage($enum::class)->enumMappingExists($enum);
    }

    /**
     * @template TUnitEnum of UnitEnum
     * @param class-string<TUnitEnum> $enumClass
     *
     * @return TUnitEnum|null
     */
    public function tryStringToEnum(string $enumClass, string $mappingValue): ?UnitEnum
    {
        $mappingStorage = $this->getMappingStorage($enumClass);

        if (! $mappingStorage->mappingValueExists($mappingValue)) {
            return null;
        }
        $enum = $mappingStorage->mappingValueToEnum($mappingValue);
        \assert($enum instanceof $enumClass);

        return $enum;
    }

    /**
     * @template TUnitEnum of UnitEnum
     * @param class-string<TUnitEnum> $enumClass
     *
     * @return TUnitEnum
     * @throws UnexpectedEnumMappingValueException
     */
    public function stringToEnum(string $enumClass, string $mappingValue): UnitEnum
    {
        $enum = $this->tryStringToEnum($enumClass, $mappingValue)
            ?? throw new UnexpectedEnumMappingValueException(
                "{$enumClass}: `{$mappingValue}` not found",
            );
        \assert($enum instanceof $enumClass);

        return $enum;
    }

    public function enumToString(UnitEnum $enum): string
    {
        try {
            $value = $this->getMappingStorage($enum::class)->enumToMappingValue($enum);
            if (! \is_string($value)) {
                throw new LogicException('Mapping value is not a string.');
            }

            return $value;
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException(
                message: $exception->getMessage(),
                previous: $exception,
            );
        }
    }

    public function tryEnumToString(UnitEnum $enum): ?string
    {
        return $this->enumMappingExists($enum) ? $this->enumToString($enum) : null;
    }

    /**
     * @param UnitEnum[] $enums
     * @return string[]
     */
    public function enumsToStrings(array $enums): array
    {
        return \array_map(fn (UnitEnum $enum): string => $this->enumToString($enum), $enums);
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     * @return string[]|int[]
     */
    public function getValues(string $enumClass): array
    {
        return $this->getMappingStorage($enumClass)->getAllMappingValues();
    }

    /**
     * Replaces keys of given array according to enum mapping
     * @template TValue
     * @param class-string<BackedEnum> $enumClass
     * @param array<string|int, TValue> $arr
     * @return array<int|string, TValue>
     */
    public function remapKeys(string $enumClass, array $arr): array
    {
        $mappingStorage = $this->getMappingStorage($enumClass);

        $isEnumOfTypeString = $this->isEnumOfTypeString($enumClass);

        $remapped = [];
        foreach ($arr as $k => $v) {
            // we need to always cast keys to string if we want to create enum with string type.
            // this is because array's keys with numeric string type converted to int in PHP
            // ref. https://stackoverflow.com/a/4100765 and https://www.php.net/manual/en/language.types.array.php
            if ($isEnumOfTypeString) {
                $k = (string) $k;
            }

            $enum = $enumClass::tryFrom($k)
                ?? throw new InvalidArgumentException(
                    "Key ({$k}) isn't backed value of enum ({$enumClass})"
                );
            if ($this->enumMappingExists($enum)) {
                $remapped[$mappingStorage->enumToMappingValue($enum)] = $v;
            }
        }

        return $remapped;
    }

    /**
     * @param class-string<BackedEnum> $enumClass
     */
    private function isEnumOfTypeString(string $enumClass): bool
    {
        $reflectionEnum = new ReflectionEnum($enumClass);

        return $reflectionEnum->isBacked() && $reflectionEnum->getBackingType()->getName() === 'string';
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     */
    private function getMappingStorage(string $enumClass): EnumMappingMemoryStorage
    {
        $this->mappings[$enumClass] ??= $this->createMappingStorage($enumClass);

        return $this->mappings[$enumClass];
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     */
    private function createMappingStorage(string $enumClass): EnumMappingMemoryStorage
    {
        $mappingStorage = new EnumMappingMemoryStorage($enumClass);
        $enumReflection = new ReflectionEnum($enumClass);
        foreach ($enumReflection->getCases() as $caseReflection) {
            if (! $caseReflection instanceof ReflectionEnumUnitCase) {
                continue;
            }

            /** @var UnitEnum $enum */
            $enum = $enumReflection->getCase($caseReflection->getName())->getValue();

            $attrReflections = $caseReflection->getAttributes();
            foreach ($attrReflections as $attrReflection) {
                $attr = $attrReflection->newInstance();
                if (! $attr instanceof EnumMappingInterface || $attr->getType() !== static::class) {
                    continue;
                }

                if ($attr instanceof EnumMappingValue) {
                    $mappingStorage->storeMapping($enum, $attr->getValue());
                } elseif ($attr instanceof EnumMappingSame) {
                    if (! $caseReflection instanceof ReflectionEnumBackedCase) {
                        throw new LogicException(\sprintf(
                            '`%s` has `%s` attribute. Only BackedEnums can use this attribute.',
                            $enumClass,
                            EnumMappingSame::class,
                        ));
                    }
                    $mappingStorage->storeMapping($enum, $caseReflection->getBackingValue());
                }
            }
        }

        return $mappingStorage;
    }
}
