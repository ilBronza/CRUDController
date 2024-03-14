<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\Buttons\Button;
use Illuminate\Database\Eloquent\Model;

trait CRUDModelButtonsTrait
{
    public static function getGoToListButton() : Button
    {
        return Button::create([
                'href' => route(pluralClass(static::class) . '.index'),
                'translatedText' => static::getTranslation('goToList'),
                'icon' => 'list'
            ]);
    }

    static function getCreateButton(array $routeParameters = []) : Button
    {
        $button = Button::create([
            'href' => route(static::getStaticRouteBasename() . '.create', $routeParameters), 
            'text' => 'generals.create' . class_basename(static::class),
            'icon' => 'plus'
        ]);

        $button->setHtmlClass('uk-margin-large-left');
        $button->setPrimary();

        return $button;
    }

    static function getReorderButton(array $routeParameters = []) : Button
    {
        return Button::create([
            'href' => static::make()->getReorderUrl(),
            // 'href' => route(static::getModelRoutesPrefix() . static::getPluralCamelcaseClassBasename() . '.reorder', $routeParameters), 
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