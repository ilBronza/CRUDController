<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Support\Collection;

trait CRUDBrotherhoodTrait
{
    public function getBrothers(string $foreign = 'parent_id') : ? Collection
    {
        if(! in_array($foreign, array_keys($this->getAttributes())))
            throw new \Exception('No ' . $foreign . ' or $brotherhoodFields array declared for ' . class_basename($this) . '. Which foreign key do I have do check to find brothers models?');

        if(! $this->$foreign)
            return null;

        return static::where($this->getKeyName(), '!=', $this->getKey())->where($foreign, $this->$foreign)->get();
    }

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