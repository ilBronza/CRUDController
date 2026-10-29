<?php

namespace IlBronza\CRUD\Catalog\Filters;

use IlBronza\CRUD\Interfaces\CatalogFilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

abstract class Filter implements CatalogFilterInterface
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
}
