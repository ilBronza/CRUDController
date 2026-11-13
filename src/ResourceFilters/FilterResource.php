<?php

namespace IlBronza\CRUD\ResourceFilters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use LogicException;

final class FilterResource
{
    public readonly ResourceFilterHelper $helper;
    public readonly array $definitions;
    public readonly string $modelClass;

    public function __construct(public readonly string $key, public readonly array $config)
    {
        $base = $config['model'];
        $projectClass = method_exists($base, 'gpc') ? $base::gpc() : $base;
        $this->modelClass = is_a($projectClass, $base, true) ? $projectClass : $base;
        $this->helper = app()->makeWith($config['helper'], [
            'modelClass' => $this->modelClass,
        ]);
        $this->definitions = $this->helper->transformFilters($this->helper->getFilters());
    }

    public function matches(Model $model): bool
    {
        return is_a($model, $this->config['model']) || is_a($model, $this->modelClass);
    }

    public function label(): string
    {
        return __($this->config['label']);
    }

    /** A submission replaces this resource. Old session data is checked one criterion at a time. */
    public function values(array $input, bool $submission): array
    {
        $values = [];
        foreach ($this->definitions as $name => $definition) {
            $value = $input[$name] ?? null;
            if ($value === null || $value === '' || $value === [])
                continue;
            try {
                $values[$name] = $this->validateCriterion($name, $definition, $value, $submission);
            } catch (ValidationException $exception) {
                if ($submission)
                    throw $exception;
            }
        }
        ksort($values);
        return $values;
    }

    private function validateCriterion(string $name, array $definition, mixed $value, bool $submission): mixed
    {
        $multiple = $definition['multiple'] ?? false;
        $cast = $definition['cast'] ?? 'string';
        $typeRule = match ($cast) {
            'boolean', 'integer', 'string' => $cast,
            default => throw new LogicException("Unsupported filter type $cast"),
        };
        $rules = [
            'bail',
            'required',
            $typeRule,
            ...($definition['rules'] ?? []),
        ];
        $validation = $multiple ? [
            $name => [
                'bail',
                'array',
                'list',
            ],
            "$name.*" => $rules,
        ] : [
            $name => $rules,
        ];

        // Accept numeric JSON identifiers as well as strings sent by an HTML select.
        if ($cast === 'string') {
            $stringify = fn ($v) => is_int($v) ? (string) $v : $v;
            $value = is_array($value) ? array_map($stringify, $value) : $stringify($value);
        }
        Validator::make([
            $name => $value,
        ], $validation)->validate();
        $items = array_map(fn ($item) => match ($cast) {
            'boolean' => (bool) $item,
            'integer' => (int) $item,
            'string' => $item,
        }, (array) $value);
        if ($submission && isset($definition['options'])) {
            $options = $definition['options']($items);
            foreach ($items as $item)
                if (! array_key_exists(is_bool($item) ? (int) $item : $item, $options))
                    throw ValidationException::withMessages([
                        $name => __('validation.in', [
                            'attribute' => __($definition['label']),
                        ]),
                    ]);
        }
        if (! $multiple)
            return $items[0];

        $items = array_values(array_unique($items, $cast === 'string' ? SORT_STRING : SORT_REGULAR));
        sort($items, $cast === 'string' ? SORT_STRING : SORT_REGULAR);
        return $items;
    }
}
