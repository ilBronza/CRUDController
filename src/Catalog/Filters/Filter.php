<?php

namespace IlBronza\CRUD\Catalog\Filters;

use IlBronza\CRUD\Interfaces\CatalogFilterInterface;
use IlBronza\CRUD\Interfaces\FrontendCatalogFilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

abstract class Filter implements CatalogFilterInterface, FrontendCatalogFilterInterface
{
    protected string $view;

    public function __construct(protected string $name, protected array $parameters = [])
    {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name))
            throw new InvalidArgumentException('Invalid catalog filter key.');

        $targets = array_intersect(['column', 'columns', 'scope'], array_keys($parameters));
        if (count($targets) > 1)
            throw new InvalidArgumentException("Declare a single query target for {$name}.");
    }

    public function key(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->parameters['label'] ?? $this->name;
    }

    public function control(Builder $baseQuery): array
    {
        return array_merge($this->parameters, [
            'key' => $this->key(),
            'label' => $this->label(),
            'view' => $this->parameters['view'] ?? $this->view,
        ]);
    }

    protected function validate(mixed $value, array $rules): void
    {
        $validator = Validator::make(['value' => $value], $rules, [], ['value' => $this->label()]);
        if ($validator->fails()) {
            $messages = [];
            foreach ($validator->errors()->messages() as $key => $errors)
                $messages[str_replace('value', 'filters.' . $this->key(), $key)] = $errors;

            throw ValidationException::withMessages($messages);
        }
    }

    protected function column(Builder $query): string
    {
        $column = $this->parameters['column'] ?? null;
        if (! is_string($column) || $column === '')
            throw new InvalidArgumentException("Missing column for {$this->name}.");

        return $query->getModel()->qualifyColumn($column);
    }

    public function apply(Builder $query, mixed $value): void
    {
        if (isset($this->parameters['scope'])) {
            $scope = $this->parameters['scope'];
            if (! is_string($scope) || ! $query->hasNamedScope($scope))
                throw new InvalidArgumentException("Invalid local scope for {$this->name}.");

            $query->{$scope}($value);
            return;
        }

        $this->applyValue($query, $value);
    }

    abstract protected function applyValue(Builder $query, mixed $value): void;

    public function selections(mixed $value): array
    {
        return [['label' => $this->label() . ': ' . (string) $value]];
    }

    public function frontendType(): string
    {
        $type = $this->parameters['frontendType'] ?? match (static::class) {
            TextFilter::class => 'text',
            MultipleFilter::class => 'multiple',
            NumberRangeFilter::class => 'number-range',
            BooleanFilter::class => 'boolean',
            default => null,
        };

        if (! in_array($type, ['text', 'multiple', 'number-range', 'boolean'], true))
            throw new InvalidArgumentException("Declare a frontendType or override frontendType() for {$this->name}.");

        if (isset($this->parameters['scope']) && ! isset($this->parameters['frontendValue']))
            throw new InvalidArgumentException("Declare a frontendValue for scope filter {$this->name}.");

        return $type;
    }

    public function frontendValue(Model $model): mixed
    {
        if (isset($this->parameters['frontendValue'])) {
            if (! is_callable($this->parameters['frontendValue']))
                throw new InvalidArgumentException("Invalid frontendValue for {$this->name}.");

            return ($this->parameters['frontendValue'])($model);
        }

        $columns = $this->parameters['columns'] ?? [$this->parameters['column'] ?? null];
        $values = [];
        foreach ($columns as $column) {
            if (! is_string($column) || $column === '')
                throw new InvalidArgumentException("Missing frontend column for {$this->name}.");

            $values[] = $model->getAttribute(str_starts_with($column, $model->getTable() . '.')
                ? substr($column, strlen($model->getTable()) + 1) : $column);
        }

        return count($values) === 1 ? $values[0] : $values;
    }
}
