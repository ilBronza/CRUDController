<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\Buttons\Button;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Log;

use function class_basename;
use function dd;
use function route;

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
		try
		{
			$placeholder = static::make();

			$href = $placeholder->getCreateUrl();
			$text = __('crud::crud.createNew', ['model' => $placeholder->getTranslatedClassname()]);
		}
		catch(\Exception $e)
		{
			Log::critical($e->getMessage() . ' - ' . $e->getTraceAsString());

			$href = route(static::getStaticRouteBasename() . '.create', $routeParameters);
			$text = 'generals.create' . class_basename(static::class);
		}

		$button = Button::create([
			'href' => $href,
			'text' => $text,
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