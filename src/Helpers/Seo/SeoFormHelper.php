<?php

namespace IlBronza\CRUD\Helpers\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class SeoFormHelper
{
    public static function rules(): array
    {
        $rules = ['locale' => 'required|string|max:35|regex:/^[a-zA-Z]{2,3}([_-][a-zA-Z0-9]{2,8})*$/'];
        foreach (['title', 'og_title', 'robots', 'og_type'] as $field)
            $rules[$field] = 'nullable|string|max:255';
        foreach (['description', 'og_description', 'og_image_alt'] as $field)
            $rules[$field] = 'nullable|string|max:10000';
        foreach (['canonical_url', 'og_image'] as $field)
            $rules[$field] = 'nullable|url:http,https|max:4096';
        return $rules;
    }

    public static function fields(): array
    {
        $fields = [];
        foreach (static::rules() as $name => $rules)
            $fields[$name] = ['type' => in_array($name, ['description', 'og_description', 'og_image_alt']) ? 'textarea' : 'text', 'rules' => $rules];
        return $fields;
    }

    public static function render(?Model $model = null, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        return view('crud::seo.fields', ['fields' => static::fields(), 'seo' => $model && $model->exists ? $model->getSeo($locale) : null, 'locale' => $locale]);
    }

    // Call after saving the parent, within its transaction, for both create and edit.
    public static function save(Model $model, array $data)
    {
        if (! $model->exists)
            throw new \LogicException('Save the parent before its SEO.');
        $rules = [];
        foreach (static::rules() as $field => $rule)
            $rules['seo.' . $field] = $rule;
        $validated = Validator::make(['seo' => $data], $rules)->validate()['seo'];
        $seo = $model->seo()->updateOrCreate(['locale' => $validated['locale']], $validated);
        $model->unsetRelation('seo');
        return $seo;
    }

    public static function url(Model $model, string $action = 'index', ?string $locale = null): string
    {
        return route('crud.seo.' . $action, array_filter([
            'model' => $model->getMorphClass(), 'key' => $model->getKey(), 'locale' => $locale,
        ], static fn ($value) => $value !== null));
    }

    public static function addFetcher($form, Model $model, string $position = 'bottom')
    {
        return $form->addFetcherByUrl(static::url($model), $position, ['title' => 'SEO']);
    }
}
