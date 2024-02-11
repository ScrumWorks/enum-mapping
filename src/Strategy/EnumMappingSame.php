<?php

declare(strict_types=1);

namespace ScrumWorks\EnumMapping\Strategy;

use Attribute;
use ScrumWorks\EnumMapping\AbstractEnumMapping;
use ScrumWorks\EnumMapping\EnumMappingInterface;

/**
 * @template T of AbstractEnumMapping
 * @implements EnumMappingInterface<T>
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
final readonly class EnumMappingSame implements EnumMappingInterface
{
    /**
     * @param class-string<T> $type
     */
    public function __construct(
        private string $type,
    ) {
    }

    /**
     * @return class-string<T>
     */
    public function getType(): string
    {
        return $this->type;
    }
}
