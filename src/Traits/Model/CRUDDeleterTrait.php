<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDDeleterTrait
{
    public function deleterDelete()
    {
        foreach($this->deletingRelationships as $relationship)
        {
            $thing = $this->$relationship()->make();

            $elements = $this->$relationship();

            if(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($thing)))
                $elements->withTrashed();

            foreach($elements->get() as $element)
                if(in_array('IlBronza\CRUD\Traits\Model\CRUDDeleterTrait', class_uses($element)))
                    $element->deleterDelete();
                else
                    $element->delete();
        }

        return $this->delete();
    }

    public function deleterForceDelete()
    {
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