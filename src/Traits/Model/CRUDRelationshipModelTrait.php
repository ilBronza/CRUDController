<?php

namespace ilBronza\CRUD\Traits\Model;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait CRUDRelationshipModelTrait
{
    /**
     * resolve and call getter function for possible related models
     *
     * @param string $relationship
     * @return callable
     **/
    public function getRelationshipPossibleValuesArray(string $relationship)
    {
        $getterMethodName = 'getPossible' . ucfirst(Str::plural($relationship)) . 'ValuesArray';

        return $this->$getterMethodName();
    }

    /**
     * standard method to build array elements for select
     *
     * @param Collection $elements
     * @return array
     **/
    public function buildElementsArryForSelect(Collection $elements)
    {
        $result = [];

        foreach($elements as $element)
            $result[$element->getKey()] = $element->getNameForDisplayRelation();

        return $result;
    }

    public function getNameForDisplayRelation()
    {
        return $this->name;
    }

    /**
     * get edit relation pivot row link
     *
     * calculate route to edit pivot row taking current model class and retrieving correct route
     *
     * @param Model $model
     * @return string 
     **/
    public function getRelationEditUrl(Model $model)
    {
        $routePieces = [
            $this->getPluralCamelcaseClassBasename(),
            $model->getPluralCamelcaseClassBasename(),
            'edit'
        ];

        return route(implode(".", $routePieces), [$this, $model]);
    }
}