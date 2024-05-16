<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Traits\Model\CRUDModelUsesTrait;

trait CRUDDeleterTrait
{
    use CRUDModelUsesTrait;

    public function getDeletingRelationshipsField()
    {
        if($this->deletingRelationships ?? false)
            return $this->deletingRelationships;

        if(isset(static::$deletingRelationships))
            return static::$deletingRelationships;

        throw new \Exception('Dichiara i campi static deletingRelationships nel model ' . class_basename($this));
    }

    public function deleterDelete()
    {
        if(method_exists($this, 'userCanDelete'))
            $this->userCanDelete();

        foreach($this->getDeletingRelationshipsField() as $relationship)
        {
            $elements = $this->$relationship();

            //check if needed withTrashed method
            $thing = $this->$relationship()->make();
            if(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($thing)))
                $elements->withTrashed();

            foreach($elements->get() as $element)
            {
                if(method_exists($element, 'deleterDelete'))
                    $element->deleterDelete();
                else
                    $element->delete();
            }
        }

        return $this->delete();
    }

    private function relationshipHasSoftDeletes(string $relationshipName) : bool
    {
        $related = $this->$relationshipName()->make();

        return $this->modelUsesSoftDeletes($related);
    }

    public function deleterForceDelete()
    {

        foreach($this->getDeletingRelationshipsField() as $relationship)
        {
            $elements = $this->$relationship();

            if($this->relationshipHasSoftDeletes($relationship))
                $elements->withTrashed();

            foreach($elements->get() as $element)
                if($this->modelUsesTrait($element, 'CRUDDeleterTrait'))
                    $element->deleterForceDelete();

                elseif($this->modelUsesSoftDeletes($element))
                    $element->forceDelete();

                else
                    $element->delete();

        }

        return $this->forceDelete();
    }
}