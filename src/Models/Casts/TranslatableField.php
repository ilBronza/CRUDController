<?php

namespace IlBronza\CRUD\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/** Resolve translated columns on read; write to the explicitly assigned column. */
final class TranslatableField implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $locale = request()->attributes->get('frontend.locale', app()->getLocale());

        return $locale === config('app.base_locale', 'it')
            ? $value
            : ($attributes[$locale.'_'.$key] ?? null);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}
