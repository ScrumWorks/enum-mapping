<?php

declare(strict_types=1);

namespace ScrumWorks\EnumMapping;

/**
 * @template T of AbstractEnumMapping
 */
interface EnumMappingInterface
{
    /**
     * @return class-string<T>
     */
    public function getType(): string;
}
