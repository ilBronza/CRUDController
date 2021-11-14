<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDBrotherhoodTrait
{
    public function getBrotherhoodFields()
    {
        if(! isset(static::$brotherhoodFields))
            throw new \Exception('No static $brotherhoodFields array declared for ' . class_basename($this));

        return static::$brotherhoodFields;
    }

    public function getBrotherhoodConditions()
    {
        $result = [];

        foreach($this->getBrotherhoodFields() as $field)
            $result[] = [$field, $this->{$field}];

        return $result;
    }

    public function scopeBrothers($query)
    {
        $brotherhoodFields = $this->getBrotherhoodFields();
        $brotherhoodConditions = $this->getBrotherhoodConditions();

        return $query->where($brotherhoodConditions);
    }
}