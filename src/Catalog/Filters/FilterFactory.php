<?php

namespace IlBronza\CRUD\Catalog\Filters;

use IlBronza\CRUD\Interfaces\CatalogFilterInterface;
use InvalidArgumentException;

class FilterFactory
{
    private const TYPES = [
        'text' => TextFilter::class,
        'multiple' => MultipleFilter::class,
        'number-range' => NumberRangeFilter::class,
        'boolean' => BooleanFilter::class,
    ];

    public static function makeMany(array $parameters): array
    {
        $filters = [];

        foreach ($parameters as $name => $configuration) {
            if ($configuration instanceof CatalogFilterInterface) {
                $filter = $configuration;
            } else {
                $type = $configuration['type'] ?? '';
                $class = self::TYPES[$type] ?? $type;

                if (! is_string($class) || ! is_subclass_of($class, CatalogFilterInterface::class))
                    throw new InvalidArgumentException("Unknown catalog filter type for {$name}.");

                $filter = new $class((string) $name, $configuration);
            }

            if (isset($filters[$filter->key()]))
                throw new InvalidArgumentException('Duplicate catalog filter: ' . $filter->key());

            $filters[$filter->key()] = $filter;
        }

        return $filters;
    }
}
