<?php

namespace IlBronza\CRUD\ResourceFilters;

use IlBronza\Form\Form;
use IlBronza\FormField\FormField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

abstract class ResourceFilterHelper
{
    public function __construct(public readonly string $modelClass)
    {
    }

    abstract public function getFilters(): array;

    public function transformFilters(array $filters): array
    {
        return $filters;
    }

    protected function identifiers(string $label): array
    {
        return [
            'type' => 'select',
            'label' => $label,
            'multiple' => true,
            'options' => fn (?array $ids) => $this->identifierOptions($ids),
        ];
    }

    protected function optionsQuery(): Builder
    {
        return $this->modelClass::query()->withoutGlobalScope(SessionResourceScope::class);
    }

    protected function identifierOptions(?array $ids): array
    {
        $query = $this->optionsQuery();
        if ($ids !== null)
            $query->whereKey($ids);

        $options = [];
        foreach ($query->get() as $model) {
            if (method_exists($model, 'userCanShow') && ! $model->userCanShow())
                continue;
            if (Gate::getPolicyFor($model) && Gate::denies('view', $model))
                continue;
            $options[$model->getKey()] = $model->getName() ?? (string) $model->getKey();
        }
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    public function buildForm(string $action, string $label, array $definitions, array $values): Form
    {
        $form = Form::createFromArray([
            'action' => $action,
            'method' => 'PUT',
            'cancelButton' => false,
            'submitButtonText' => __('crud::resource-filters.apply'),
        ]);
        $fieldset = $form->addFormFieldset($label, [
            'translatedLegend' => e($label),
            'canBeHidden' => false,
        ]);
        foreach ($definitions as $name => $definition) {
            $parameters = $definition['field'] ?? [];
            $parameters['name'] = $name;
            $parameters['type'] = $definition['type'];
            $parameters['label'] = e(__($definition['label']));
            $parameters['multiple'] = $definition['multiple'] ?? false;
            $parameters['value'] = $values[$name] ?? null;
            $parameters['placeholder'] ??= __($definition['label']);
            if (isset($definition['options']))
                $parameters['list'] = $definition['options'](null);
            $fieldset->addFormField(FormField::createFromArray($parameters));
        }

        return $form;
    }
}
