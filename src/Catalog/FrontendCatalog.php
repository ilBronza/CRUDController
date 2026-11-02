<?php

namespace IlBronza\CRUD\Catalog;

use IlBronza\CRUD\Interfaces\CatalogDefinitionInterface;
use IlBronza\CRUD\Interfaces\FrontendCatalogFilterInterface;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class FrontendCatalog
{
    public array $items = [];
    public array $data = [];
    public array $filters = [];
    public string $defaultSort;

    public function __construct(CatalogDefinitionInterface $definition, Builder $base, array $filters, array $controls)
    {
        $this->defaultSort = $definition->defaultSort();
        foreach ($filters as $key => $filter) {
            if (! $filter instanceof FrontendCatalogFilterInterface)
                throw new InvalidArgumentException("Filter {$key} does not support frontend catalogs.");

            $type = $filter->frontendType();
            if (! in_array($type, ['text', 'multiple', 'number-range', 'boolean'], true))
                throw new InvalidArgumentException("Invalid frontend filter type for {$key}.");

            $this->filters[$key] = array_merge(
                array_intersect_key($controls[$key], array_flip(['min', 'max', 'maxLength', 'maxSelections', 'options'])),
                ['type' => $type, 'label' => $filter->label()],
            );
            if (isset($controls[$key]['options']))
                $this->filters[$key]['options'] = (object) $controls[$key]['options'];
        }

        // The snapshot always starts from the authorized base, never from URL filters.
        $models = (clone $base)->with($definition->with())->reorder()->orderBy($base->getModel()->getQualifiedKeyName())->get();
        $sortDefinitions = $definition->sorts();
        foreach ($models as $model) {
            $values = $sorts = [];
            foreach ($filters as $key => $filter)
                $values[$key] = $filter->frontendValue($model);
            foreach ($sortDefinitions as $key => $sort) {
                $column = $sort['column'];
                $prefix = $model->getTable() . '.';
                $sorts[$key] = $model->getAttribute(str_starts_with($column, $prefix) ? substr($column, strlen($prefix)) : $column);
            }

            $this->items[] = $model->toCatalogItem();
            $this->data[] = ['filters' => $values, 'sorts' => $sorts];
        }
    }

    public function payload(Catalog $catalog): array
    {
        return [
            'filters' => (object) $this->filters,
            'sorts' => $catalog->sorts,
            'defaultSort' => $this->defaultSort,
            'perPage' => $catalog->results->perPage(),
            'context' => (object) $catalog->context,
            'items' => $this->data,
            'messages' => [
                'zero' => trans_choice('crud::catalog.results', 0),
                'one' => trans_choice('crud::catalog.results', 1),
                'many' => trans_choice('crud::catalog.results', 2, ['count' => ':count']),
                'empty' => __('crud::catalog.empty'),
                'remove' => __('crud::catalog.remove'),
                'previous' => __('crud::catalog.previous'), 'next' => __('crud::catalog.next'),
                'pagination' => __('crud::catalog.pagination'), 'page' => __('crud::catalog.page'),
                'yes' => __('crud::catalog.yes'), 'no' => __('crud::catalog.no'),
                'from' => __('crud::catalog.from'), 'to' => __('crud::catalog.to'),
                'invalid' => __('crud::catalog.invalid_filters'), 'range' => __('crud::catalog.invalid_range'),
            ],
        ];
    }
}
