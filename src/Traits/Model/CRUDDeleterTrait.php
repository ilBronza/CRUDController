<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDDeleterTrait
{
    public function deleterDelete()
    {
        if(! isset($this->deletingRelationships))
            throw new \Exception('Dichiara i campi deletingRelationships nel model ' . class_basename($this));

        foreach($this->deletingRelationships as $relationship)
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
        if(! isset($this->deletingRelationships))
            throw new \Exception('Dichiara i campi deletingRelationships nel model ' . class_basename($this));

        foreach($this->deletingRelationships as $relationship)
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