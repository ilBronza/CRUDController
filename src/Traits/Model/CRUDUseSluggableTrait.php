<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDUseSluggableTrait
{
    use \Cviebrock\EloquentSluggable\Sluggable;

    private function getUniqueFieldUpdateValidationRule(string $fieldName)
    {
        return implode(',', [
            'unique:' . $this->getTable(),
            $fieldName,
            $this->getKey(),
            $this->getKeyName()
        ]);
    }

    /**
     * get an extrarule to updateCRUDValidation to check alias uniqueness for model instance
     *
     * @return array
    */
    public function getUpdateInstanceValidationRules()
    {
        $uniqueAliasRule = $this->getUniqueFieldUpdateValidationRule('alias');

        return [
            'alias' => 'string|nullable|max:255|' . $uniqueAliasRule
        ];
    }
}