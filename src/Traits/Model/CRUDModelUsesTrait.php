<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;

trait CRUDModelUsesTrait
{
    public function modelUsesSoftDeletes(Model $model) : bool
    {
    	return $this->modelUsesTrait($model, 'SoftDeletes');
    }

    public function modelUsesTrait(Model $model, string $traitName) : bool
    {
        $traits = $this->getUsedTraits($model);

        foreach($traits as $trait)
        	if(strpos($trait, $traitName))
        		return true;

        return false;
    }

    private function getUsedTraits(Model $model) : array
    {
        $parentClasses = class_parents($model);

        $traits = class_uses($model);

        foreach ($parentClasses as $parentClass)
            $traits = array_merge(
                $traits,
                class_uses($parentClass)
            );

        $traits = array_merge(
            $traits,
            $this->getTraitsUsesTraits($traits)
        );
       
        return $traits;
    }

    private function getTraitsUsesTraits(array $traits) : array
    {
        foreach($traits as $trait)
            $traits = array_merge(
                $traits,
                $this->getTraitUsedTraits($trait)
            );

        return $traits;
    }

    private function getTraitUsedTraits(string $trait) : array
    {
        $traits = class_uses($trait);

        return $this->getTraitsUsesTraits($traits);
    }

}