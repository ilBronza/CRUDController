<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait CRUDManyToManyTreeTrait
{
    abstract function getManyToManyRelationClass() : string;

    public function getManyToManyRelationPivotFields() : array
    {
        $model = $this->getManyToManyRelationClass()::make();

        return $model->getManyToManyRelationPivotFields();
    }

    public function getManyToManyRelationTable() : string
    {
        $model = $this->getManyToManyRelationClass()::make();

        return $model->getTable();
    }

    public function getManyToManyParentKeyName() : string
    {
        $model = $this->getManyToManyRelationClass()::make();

        return $model->getParentKeyName();
    }

    public function getManyToManyChildKeyName() : string
    {
        $model = $this->getManyToManyRelationClass()::make();

        return $model->getChildKeyName();
    }

    public function manageancestorsTreeResult(BelongsToMany $result, string $mainComponentName = null)
    {
        $result->using($this->getManyToManyRelationClass())
            ->withPivot(
                $this->getManyToManyRelationPivotFields()
            );

        if ($mainComponentName)
        {
            $result->where($this->getManyToManyRelationTable() . '.main_component', $mainComponentName);
        }

        return $result;
    }

    public function ancestors(string $mainComponentName = null)
    {
        $result = $this->belongsToMany(
            self::class,
            $this->getManyToManyRelationTable(),
            $this->getManyToManyChildKeyName(),
            $this->getManyToManyParentKeyName()
        );

        return $this->manageancestorsTreeResult($result, $mainComponentName);
    }

    public function descendants(string $mainComponentName = null)
    {
        $result = $this->belongsToMany(
            self::class,
            $this->getManyToManyRelationTable(),
            $this->getManyToManyParentKeyName(),
            $this->getManyToManyChildKeyName()
        );

        return $this->manageancestorsTreeResult($result, $mainComponentName);
    }

    public function getAncestors(string $mainComponentName = null) : Collection
    {
        return $this->ancestors($mainComponentName)->get();
    }

    public function getDescendants(string $mainComponentName = null) : Collection
    {
        return $this->descendants($mainComponentName)->get();
    }

    private function deleteAncestorRelation($ancestor = null) : bool
    {
        if (!$ancestor)
        {
            return false;
        }

        $searchParameters = [
            'parent_id' => $ancestor->getKey(),
            'child_id' => $this->getKey()
        ];

        return $this->getManyToManyRelationClass()::where($searchParameters)->delete();
    }

    public function deleteWithDescendants($ancestor = null)
    {
        $this->deleteAncestorRelation($ancestor);

        if ($this->ancestors()->count() > 0)
        {
            return false;
        }

        $descendants = $this->getDescendants();

        foreach ($descendants as $descendant)
        {
            $descendant->deleteWithDescendants($this);
        }

        $this->delete();
    }

}