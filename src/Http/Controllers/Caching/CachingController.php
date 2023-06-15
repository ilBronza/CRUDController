<?php

namespace IlBronza\CRUD\Http\Controllers\Caching;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class CachingController extends Controller
{
	abstract public function getModelClass() : string;

	public function getModelRelations() : ? array
	{
		return $this->getModelClass()::getAutomaticCachingRelationships();
	}

	public function getElements(array $missingIds = null)
	{
		$query = $this->getModelClass()::query();

		if($relations = $this->getModelRelations())
			$query->with($relations);

        if($missingIds)
        	$query->whereIn(
        		$this->getPlaceholderElement()->getKeyName(),
        		$missingIds
        	);

		return $query->get();
	}

    public function _getIndexElements(array $missingIds = null)
    {
        if(is_null($missingIds)&&(count($missingIds) == 0))
            return null;

        return $this->getElements($missingIds);
    }

	public function build()
	{
		$elements = $this->getElements();

		foreach($elements as $element)
			$element->storeInCache();
	}

	public function getPlaceholderElement() : Model
	{
		return $this->getModelClass()::make();
	}

	public function getIndexModelIds() : array
	{
        $placeholder = $this->getPlaceholderElement();

        return DB::table(
            $placeholder->getTable()
        )->select(
            $placeholder->getKeyName()
        )->get()
        ->pluck(
            $placeholder->getKeyName()
        )->toArray();
	}

	public function getCachedModelsByIds(array $ids)
	{
        $cacheKeys = [];

        foreach($ids as $id)
            $cacheKeys[] = $this->getModelClass()::staticCacheKey($id);

        return cache()->many($cacheKeys);
	}

	public function getIndexMissingIds($totalElementIds, $cachedModels)
	{
        $cachedIds = array_column(
        	$cachedModels,
        	'id'
        );

        return array_diff(
				$totalElementIds,
				$cachedIds
        	);
	}

	public function setExecutionLimitsByMissingIds($missingIds)
	{
        $maxExecutionSeconds = (int) (count($missingIds) / 10);

        if($maxExecutionSeconds > 300)
            $maxExecutionSeconds = 300;

        if($maxExecutionSeconds < 30)
            $maxExecutionSeconds = 30;

        ini_set('max_execution_time', $maxExecutionSeconds);
        ini_set('memory_limit', "-1");		
	}

	public function buildMissing()
	{
		$totalElementIds = $this->getIndexModelIds();

		$cachedModels = $this->getCachedModelsByIds(
			$totalElementIds
		);

		$missingIds = $this->getIndexMissingIds(
			$totalElementIds,
			$cachedModels
		);

		$this->setExecutionLimitsByMissingIds(
			$missingIds
		);

		$missingElements = $this->_getIndexElements($missingIds);

		foreach($missingElements as $missingElement)
			$missingElement->storeInCache();
	}
}