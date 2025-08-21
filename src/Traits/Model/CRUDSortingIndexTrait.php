<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

trait CRUDSortingIndexTrait
{
    public static function bootCRUDSortingIndexTrait()
    {
        static::creating(function (Model $model)
        {
            if($model->parent_id)
                $model->assignSortingIndex();
        });
    }








    public function assignSortingIndex()
    {
        $result = static::query()->where($this->getParentKeyName(), $this->getParentId())
            ->selectRaw('max(sorting_index) as max_sorting_index')
            ->pluck('max_sorting_index');

        $maxIndex = $result->pop() ?? 0;

        $this->sorting_index = $maxIndex + 1;
    }
}