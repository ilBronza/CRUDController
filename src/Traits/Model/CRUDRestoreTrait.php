<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDRestoreTrait
{
    public function getRestoringRelationshipsField() : array
    {
        if($this->restoringRelationships ?? false)
            return $this->restoringRelationships;

        if(isset(static::$restoringRelationships))
            return static::$restoringRelationships;

        return $this->getDeletingRelationshipsField();
    }

    public function restoreWithRelated()
    {
        $restoringRelationships = $this->getRestoringRelationshipsField();

        $this->restore();

        static::where('id', $this->getKey())->withTrashed()->update([
            'deleted_at' => null
        ]);

        foreach($restoringRelationships as $relationship)
            foreach($this->{$relationship}()->onlyTrashed()->get() as $related)
                $related->restoreWithRelated();
    }
}