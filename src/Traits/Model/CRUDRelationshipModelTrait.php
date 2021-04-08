<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait CRUDRelationshipModelTrait
{
    use CRUDDeleterTrait;

    // public function owns(Model $model)
    // {
    //     if(Auth::user()->hasRole('superadmin'))
    //         return true;

    //     if($model->user_id == $this->getKey())
    //         return true;

    //     mori($model);
    // }

    /**
     * resolve and call getter function for possible related models
     *
     * @param string $relationship
     * @return callable
     **/
    public function _getRelationshipPossibleValuesArray(string $relationship)
    {
        $relationModelClassBaseName = get_class($this->{$relationship}()->getRelated());

        $elements = $relationModelClassBaseName::all();

        return $this->buildElementsArryForSelect($elements);
    }

    /**
     * resolve and call getter function for possible related models
     *
     * @param string $relationship
     * @return callable
     **/
    public function getRelationshipPossibleValuesArray(string $relationship)
    {
        if($relationship == 'parent')
        {
            if(! isset($this->parentingTrait))
                throw new \Exception('Aggiungere ParentingTrait al model ' . class_basename($this));

            return $this->getParentPossibleValuesArray();
        }

        $getterMethodName = 'getPossible' . ucfirst(Str::plural($relationship)) . 'ValuesArray';

        if(method_exists($this, $getterMethodName))
            return $this->$getterMethodName();

        return $this->_getRelationshipPossibleValuesArray($relationship);
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
        return $this->getName();
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