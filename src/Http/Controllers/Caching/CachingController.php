<?php

namespace IlBronza\CRUD\Http\Controllers\Caching;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class CachingController extends Controller
{
	abstract public function getModelClass() : string;
	abstract public function getScope() : string|null|array;

	public function getModelRelations() : ? array
	{
		return $this->getModelClass()::getAutomaticCachingRelationships();
	}

	public function applicateScope($query)
	{
		if(! $scope = $this->getScope())
			return $query;

		if(is_string($scope))
			return $query->$scope();

		if(is_array($scope))
		{
			foreach($scope as $_scope)
				$query->$_scope();

			return $query->$scope();			
		}

		throw new \Exception('Scope type not valid ' . gettype($scope));
	}

	public function getElements(array $missingIds = null)
	{
		$query = $this->getModelClass()::query();

		if($relations = $this->getModelRelations())
			$query->with($relations);

		$query = $this->applicateScope($query);

		Log::info(json_encode($missingIds));

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

		dddl($elements);

		foreach($elements as $element)
			$element->storeInCache();
		
		return view('test', compact('elements'));
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
        )
		->whereNull('deleted_at')
		->select(
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
        ); //ritorna array vuoto perchè non ci sono chiavi "id", in cachedModels ci sono elementi così: "order-5421ef0b-b561-42fa-81c8-a6c7a129e534" => null


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


        ini_set('max_execution_time', '-1');
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

		return view('test');
	}
}