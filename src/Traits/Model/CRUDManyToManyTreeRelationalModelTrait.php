<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDManyToManyTreeRelationalModelTrait
{
    static $parentKeyName = 'parent_id';
    static $childKeyName = 'child_id';

    public function getParentKeyName()
    {
        return static::$parentKeyName;
    }

    public function getChildKeyName()
    {
        return static::$childKeyName;
    }

    public function parent()
    {
        return $this->belongsTo($this->getRelatedClassName(), $this->getParentKeyName());
    }

    public function child()
    {
        return $this->belongsTo($this->getRelatedClassName(), $this->getChildKeyName());
    }
}
