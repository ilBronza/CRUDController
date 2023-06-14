<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Traits\Model\CRUDDeleterTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelButtonsTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelRoutingTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelUserRightsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait CRUDModelTrait
{
    static $teaserFields = [];

    use CRUDModelRoutingTrait;
    use CRUDModelButtonsTrait;
    use CRUDModelUserRightsTrait;

    // use LogsActivity;
    use CRUDDeleterTrait;

    // public function hasOwnership()
    // {
    //     if(isset($this->hasOwnership))
    //         return $this->hasOwnership;

    //     return true;
    // }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function printJsonFieldHtml($array)
    {
        return view('formfield::show.uikit._json', ['arrayElement' => $array])->render();
    }

    public function getBrowserTitle()
    {
        return $this->getName();
    }

    public function getNestableName() : ? string
    {
        return $this->getName();
    }

    public function getNestableIndex() : int
    {
        return $this->sorting_index ?? 0;
    }

    public function getNestableKey() : string
    {
        return config('crud.nestableLeadingId') . $this->getKey();
    }

    public static function getPluralCamelcaseClassBasename()
    {
        return Str::plural(static::getCamelcaseClassBasename());
    }

    public static function getCamelcaseClassBasename()
    {
        return lcfirst(class_basename(static::class));
    }

    public function getTranslationsFileName()
    {
        if($this->translationsFilename ?? false)
            return $this->translationsFilename;

        return Str::plural(
            Str::camel(
                class_basename($this)
            )
        );
    }

    public static function getNameFieldName()
    {
        return static::$nameField ?? 'name';
    }

    public function getName()
    {
        $nameField = $this->getNameFieldName();

        return $this->{$nameField};
    }

    public function getTranslatedClassname()
    {
        return trans('crudModels.' . $this->getCamelcaseClassBasename());
    }
    //Rimuovere anche _teaser del pacchetto CRUD, come anche lo show
    //DEPRECATO, non voglio niente che non abbia array
    // public function getTeaserFields()
    // {
    //     return $this->teaserFields;
    // }
}