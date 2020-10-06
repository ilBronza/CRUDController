<?php

namespace ilBronza\CRUD\Traits\Model;

trait CRUDDeleterTrait
{
    public function deleterForceDelete()
    {
        foreach($this->deletingRelationships as $relationship)
        {
            $thing = $this->$relationship()->make();

            $elements = $this->$relationship();

            if(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($thing)))
                $elements->withTrashed();

            foreach($elements->get() as $element)
                if(in_array('ilBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait', class_uses($element)))
                    $elements->deleterForceDelete();
                elseif(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($element)))
                    $elements->forceDelete();
                else
                    $elements->delete();
        }

        return $this->forceDelete();
    }
}