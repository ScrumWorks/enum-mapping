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

/**
 * @template TUnitEnum of UnitEnum
 */
abstract class AbstractEnumMapping
{
    /**
     * @var array<class-string<TUnitEnum>, EnumMappingMemoryStorage<TUnitEnum, int|string>>
     */
    private array $mappings = [];

    /**
     * @param TUnitEnum $enum
     */
    public function enumMappingExists(UnitEnum $enum): bool
    {
        return $this->getMappingStorage($enum::class)->enumMappingExists($enum);
    }

    /**
     * @param class-string<TUnitEnum> $enumClass
     * @return TUnitEnum|null
     */
    public function tryStringToEnum(string $enumClass, string $mappingValue): ?UnitEnum
    {
        $mappingStorage = $this->getMappingStorage($enumClass);

        if (! $mappingStorage->mappingValueExists($mappingValue)) {
            return null;
        }
        $mappingValue = $mappingStorage->mappingValueToEnum($mappingValue);

        return $mappingValue;
    }

    /**
     * @param class-string<TUnitEnum> $enumClass
     * @throws UnexpectedEnumMappingValueException
     * @return TUnitEnum
     */
    public function stringToEnum(string $enumClass, string $mappingValue): UnitEnum
    {
        return $this->tryStringToEnum($enumClass, $mappingValue)
            ?? throw new UnexpectedEnumMappingValueException(
                "{$enumClass}: `{$mappingValue}` not found",
            );
    }

    /**
     * @param TUnitEnum $enum
     */
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

    /**
     * @param TUnitEnum[] $enums
     * @return string[]
     */
    public function enumsToStrings(array $enums): array
    {
        return \array_map(fn (UnitEnum $enum): string => $this->enumToString($enum), $enums);
    }

    /**
     * @param class-string<TUnitEnum> $enumClass
     * @return string[]|int[]
     */
    public function getValues(string $enumClass): array
    {
        return $this->getMappingStorage($enumClass)->getAllMappingValues();
    }

    /**
     * Replaces keys of given array according to enum mapping
     * @param class-string<TUnitEnum&BackedEnum> $enumClass
     * @param array<string|int, BackedEnum> $arr
     * @return array<int|string, BackedEnum>
     */
    public function remapKeys(string $enumClass, array $arr): array
    {
        $mappingStorage = $this->getMappingStorage($enumClass);

        $remapped = [];
        foreach ($arr as $k => $v) {
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
     * @param class-string<TUnitEnum> $enumClass
     * @return EnumMappingMemoryStorage<TUnitEnum, int|string>
     */
    private function getMappingStorage(string $enumClass): EnumMappingMemoryStorage
    {
        $this->mappings[$enumClass] ??= $this->createMappingStorage($enumClass);

        return $this->mappings[$enumClass];
    }

    /**
     * @param class-string<TUnitEnum> $enumClass
     * @return EnumMappingMemoryStorage<TUnitEnum, int|string>
     */
    private function createMappingStorage(string $enumClass): EnumMappingMemoryStorage
    {
        $mappingStorage = new EnumMappingMemoryStorage($enumClass);
        $enumReflection = new ReflectionEnum($enumClass);
        foreach ($enumReflection->getCases() as $caseReflection) {
            if (! $caseReflection instanceof ReflectionEnumUnitCase) {
                continue;
            }

            /** @var TUnitEnum $enum */
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
