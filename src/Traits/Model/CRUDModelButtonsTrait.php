<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\Buttons\Button;
use Illuminate\Database\Eloquent\Model;

trait CRUDModelButtonsTrait
{
    static function getCreateButton(array $routeParameters = []) : Button
    {
        return Button::create([
            'href' => route(static::getModelRoutesPrefix() . static::getPluralCamelcaseClassBasename() . '.create', $routeParameters), 
            'text' => 'generals.create' . class_basename(static::class),
            'icon' => 'plus'
        ]);
    }

    static function getReorderButton(array $routeParameters = []) : Button
    {
        return Button::create([
            'href' => route(static::getModelRoutesPrefix() . static::getPluralCamelcaseClassBasename() . '.reorder', $routeParameters), 
            'text' => 'generals.reorder' . class_basename(static::class),
            'icon' => 'bars-staggered'
        ]);        
    }

    static function getCreateChildButton(Moùdel $model)
    {
        $singularCamelModel = Str::camel(class_basename($model));
        $pluralRouteModel = Str::plural($singularCamelModel);

        $href = route(
            implode(".", [
                $pluralRouteModel,
                static::getPluralCamelcaseClassBasename(),
                'create'
            ]),
            [$singularCamelModel => $model->getKey()]
        );

        $text = trans('generals.create' . class_basename(static::class));

        return Button::create([
            'href' => $href,
            'text' => $text,
            'icon' => 'plus'
        ]);
    }

}