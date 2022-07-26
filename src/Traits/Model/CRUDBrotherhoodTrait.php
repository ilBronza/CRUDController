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

    public function scopeExcludingMe($query)
    {
        return $query->where(
                $this->getKeyName(),
                '!=',
                $this->getKey()
            );
    }

    public function scopeBrothers($query, bool $excludingMe = true)
    {
        $brotherhoodConditions = $this->getBrotherhoodConditions();

        if($excludingMe)
            $query->excludingMe();

        return $query->where($brotherhoodConditions);
    }
}