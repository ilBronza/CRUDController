<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDDeleterTrait
{
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

    public function deleterForceDelete()
    {
        foreach($this->getDeletingRelationshipsField() as $relationship)
        {
            $thing = $this->$relationship()->make();

            $elements = $this->$relationship();

            if(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($thing)))
                $elements->withTrashed();

            foreach($elements->get() as $element)
                if(in_array('IlBronza\CRUD\Traits\Model\CRUDDeleterTrait', class_uses($element)))
                    $element->deleterForceDelete();

                elseif(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($element)))
                    $element->forceDelete();
                else
                    $element->delete();
        }

        return $this->forceDelete();
    }
}