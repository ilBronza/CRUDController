<?php

namespace IlBronza\CRUD\Helpers\Forms;

use IlBronza\CRUD\Models\Casts\TranslatableField;
use IlBronza\Form\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** Expand only declared translated attributes, using the standard form providers. */
final class TranslatableFormFields
{
    public static function configure(FieldsetsProvider $provider): void
    {
        $casts = array_filter($provider->getModel()->getCasts(), static fn ($cast) => is_string($cast) && explode(':', $cast, 2)[0] === TranslatableField::class
        );

        if ($casts === []) {
            return;
        }

        if (! property_exists($provider, 'type') || ! in_array($provider->getType(), ['create', 'edit', 'store', 'update'], true)) {
            return;
        }

        $parameters = $provider->getParametersArray();
        $names = self::definedNames($parameters);
        $locales = self::locales();

        foreach ($parameters as $name => $fieldset) {
            $parameters[$name] = self::expandFieldset($fieldset, $provider->getModel(), $casts, $names, $locales);
        }

        $provider->setParameters($parameters);
    }

    /** Lists and labelled maps are both accepted; the base language is always first. */
    public static function locales(): array
    {
        $base = config('app.base_locale', 'it');
        $configured = config('crud.translatableFields.locales')
            ?? config('uikittemplate.availableLanguages', [$base]);
        $locales = [$base => strtoupper($base)];

        foreach ($configured as $key => $label) {
            $locale = is_int($key) ? $label : $key;

            if (! is_string($locale) || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/D', $locale)) {
                throw new InvalidArgumentException('Invalid locale in crud.translatableFields.locales.');
            }

            $locales[$locale] = is_int($key) ? strtoupper($locale) : $label;
        }

        return $locales;
    }

    public static function attachToForm(Form $form, FieldsetsProvider $provider): void
    {
        if (! self::hasTranslationTabs($provider->getParametersArray())) {
            return;
        }

        $form->addExtraView('innerBottom', 'crud::uikit.form.translatable-fields');
    }

    private static function definedNames(array $fieldsets): array
    {
        $names = [];

        foreach ($fieldsets as $fieldset) {
            foreach ($fieldset['fields'] ?? [] as $name => $field) {
                $names[$name] = true;
                $names[$field['name'] ?? $name] = true;
            }

            $names += self::definedNames($fieldset['fieldsets'] ?? []);
        }

        return $names;
    }

    private static function expandFieldset(array $fieldset, Model $model, array $casts, array $names, array $locales): array
    {
        // Providers can be prepared more than once; never expand generated tabs again.
        if ($fieldset['containerHtmlAttributes']['data-crud-translations'] ?? false) {
            return $fieldset;
        }

        foreach ($fieldset['fieldsets'] ?? [] as $name => $nested) {
            $fieldset['fieldsets'][$name] = self::expandFieldset($nested, $model, $casts, $names, $locales);
        }

        $translations = [];
        $base = config('app.base_locale', 'it');
        $attributes = $model->getAttributes();

        foreach ($fieldset['fields'] ?? [] as $name => $field) {
            $attribute = $field['name'] ?? $name;

            if (! isset($casts[$attribute]) || isset($field['relation'])) {
                continue;
            }

            foreach ($locales as $locale => $label) {
                $column = $locale === $base ? $attribute : $locale.'_'.$attribute;
                $requestName = $locale === $base ? $name : $locale.'_'.$name;

                // Explicitly declared translation fields retain their own layout and rules.
                if ($locale !== $base && (isset($names[$column]) || isset($names[$requestName]))) {
                    continue;
                }

                $translated = $field;
                $translated['name'] = $column;
                $translated['label'] = is_string($field['label'] ?? null)
                    ? $field['label']
                    : trans(($field['translationPrefix'] ?? $fieldset['translationPrefix'] ?? 'fields').'.'.$attribute);

                // An empty string also prevents FormField from resolving a null through the cast.
                $translated['value'] = array_key_exists($column, $attributes)
                    ? ($attributes[$column] ?? '')
                    : ($locale === $base ? ($field['value'] ?? $field['default'] ?? '') : '');

                if ($locale !== $base) {
                    $translated['rules'] = self::optionalRules($field['rules']);
                    $translated['required'] = false;
                    unset($translated['default']);

                    if (is_string($field['id'] ?? null)) {
                        $translated['id'] = $locale.'_'.$field['id'];
                    }
                }

                $translations[$locale][$requestName] = $translated;
            }

            unset($fieldset['fields'][$name]);
        }

        if ($translations === []) {
            return $fieldset;
        }

        if (count($locales) === 1) {
            $fieldset['fields'] += $translations[$base];

            return $fieldset;
        }

        $tabs = [
            'fields' => [],
            'showLegend' => false,
            'canBeHidden' => false,
            'showFieldsetsAsSwitcher' => true,
            'containerHtmlAttributes' => ['data-crud-translations' => 'data-crud-translations=1'],
            'fieldsets' => [],
        ];

        foreach ($translations as $locale => $fields) {
            $tabs['fieldsets'][$locale] = [
                'fields' => $fields,
                'legend' => $locales[$locale],
                'translateLegend' => false,
                'showLegend' => false,
                'canBeHidden' => false,
                'columns' => $fieldset['columns'] ?? 1,
                'containerHtmlAttributes' => ['data-crud-translation-locale' => 'data-crud-translation-locale='.$locale],
            ];
        }

        $key = 'crud_translations';
        while (isset($fieldset['fieldsets'][$key])) {
            $key .= '_';
        }
        $fieldset['fieldsets'][$key] = $tabs;

        return $fieldset;
    }

    private static function optionalRules(array|string $rules): array
    {
        $rules = is_string($rules) ? explode('|', $rules) : $rules;
        $rules = array_filter($rules, static function ($rule) {
            $name = is_string($rule) ? explode(':', $rule, 2)[0] : null;

            return ! in_array($name, ['required', 'present', 'filled'], true)
                && ! str_starts_with($name ?? '', 'required_')
                && ! str_starts_with($name ?? '', 'present_');
        });

        if (! in_array('nullable', $rules, true)) {
            $rules[] = 'nullable';
        }

        return array_values($rules);
    }

    private static function hasTranslationTabs(array $fieldsets): bool
    {
        foreach ($fieldsets as $fieldset) {
            if (($fieldset['containerHtmlAttributes']['data-crud-translations'] ?? false)
                || self::hasTranslationTabs($fieldset['fieldsets'] ?? [])) {
                return true;
            }
        }

        return false;
    }
}
