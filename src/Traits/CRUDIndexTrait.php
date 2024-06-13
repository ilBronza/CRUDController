<?php

namespace IlBronza\CRUD\Traits;

use Auth;
use IlBronza\Datatables\Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
// use \newdatatable;

trait CRUDIndexTrait
{
	public string $caption;

	public function getTable() : Datatables
	{
		return $this->table;
	}

	public function getSelectRow()
	{
		return $this->selectRow ?? false;
	}

	public function getTableFieldsGroupsByFile(array $keys) : array|false
	{
		$groups = [];

		foreach($keys as $key)
		{
			$getterMethod = "get" . ucfirst($key) . "FieldsArray";

			if(! method_exists($this, $getterMethod))
			{
				if($this->debugMode())
					throw new \Exception('Dichiara ' . $getterMethod . ' in ' . get_class($this));

				return false;
			}

			$groups[$key] = $this->$getterMethod();
		}

		return $groups;
	}

	/**
	 * takes all the necessary fieldsGroups by key
	 *
	 * @param string|string $fullQualifiedClass
	 * @param array|string $keys
	 *
	 * @return array
	 */
	public function getTableFieldsGroups(string|array $keys)
	{
		if(! is_array($keys))
			$keys = [$keys];

		if($groups = $this->getTableFieldsGroupsByFile($keys))
			return $groups;

		$groups = [];

		foreach ($keys as $key)
			// if(($table = static::NEWgetTableFieldsGroup($key)) !== null)
			if(($table = $this->getTableFieldsGroup($key)) !== null)
				$groups[$key] = $table;

		return $groups;
	}

	public function getTableFieldsGroup(string $key)
	{
		if(($table = $this::$tables[$key]?? null) === null)
			return null;

		// if(isset($table['fields']))
		//     return $table['fields'];

		return $table;
	}

	public function userCanCreate(User $user = null)
	{
		if(! $user)
			$user = Auth::user();

		if(! $this->methodIsAllowed('index'))
			return false;

		return $this->getModelClass()::userCanCreate($user);
	}

	public function addCreateButton()
	{
		$createButton = $this->getCreateNewModelButton();

		$this->table->addButton($createButton);
	}

	private function canReorder()
	{
		return in_array('reorder', $this->allowedMethods);
	}

	private function manageReorderButton()
	{
		if(! $this->canReorder())
			return ;

		$reorderButton = $this->getReorderButton();

		$this->table->addButton($reorderButton);
	}

	private function manageCreateButton()
	{
		if($this->avoidCreateButton ?? false)
			return ;

		try
		{
			if(! $this->userCanCreate())
				return ;
		}
		catch(\Exception $e)
		{
			throw new \Exception('Associa il trait CRUDModelTrait al model ' . $this->getModelClass() . '. ' . $e->getMessage());
		}

		$createButton = $this->getCreateNewModelButton();

		$this->table->addButton($createButton);
	}

	public function getPageLength()
	{
		return $this->pageLength ?? 50;
	}

	private function addIndexButtonsToTable()
	{
		try
		{
			$this->manageCreateButton();			
		}
		catch(\Exception $e)
		{
			Log::critical('Usa avoid create button per evitare l\'eccezione: ' . $e->getMessage());
		}
		
		$this->manageReorderButton();

		$this->addIndexButtons();
	}

	public function addIndexButtons() { }

	private function getTableName()
	{
		return Str::slug($this->getModelClassBasename());
	}

	public function beforeRenderIndex() { }

	public function getCaption() : ? string
	{
		return $this->caption ?? null;
	}

	public function manageTableCaption()
	{
		if($caption = $this->getCaption())
			$this->getTable()->setCaption($caption);
	}

	public function getRowSelectCheckboxes()
	{
		return $this->rowSelectCheckboxes;
	}

	public function _index(Request $request, string $tableName = null, array $fieldsGroupsNames = null, callable $elementsGetter = null, bool $selectRow = false, array $tableVariables = [], string $baseModel = null)
	{
		if(! $tableName)
			$tableName = $this->getTableName();

		if(! $fieldsGroupsNames)
			$fieldsGroupsNames = $this->getIndexFieldsGroups();

		if(! $selectRow)
			$selectRow = $this->getSelectRow();

		$this->table = Datatables::create(
			$tableName,
			$this->getTableFieldsGroups($fieldsGroupsNames),
			function() use($elementsGetter)
			{
				if($elementsGetter)
					return $elementsGetter();

				return $this->getIndexElements();
			},
			$selectRow ? : $this->getRowSelectCheckboxes(),
			$tableVariables,
			$baseModel ?? $this->getModelClass()
		);

        if((request()->ajax()) && (! request()->ibFetcher))
			return $this->table->renderPage();

		$this->table->addBaseModelClass($this->getModelClass());

		$this->table->setPageLength($this->getPageLength());

		if(isset($this->parentModel)&&($this->mustDisplayParentModel()))
			$this->table->addParentModel($this->parentModel);

		$this->addIndexButtonsToTable();

		$this->manageTableCaption();

		$this->beforeRenderIndex();

		$this->shareExtraViews();

		return $this->table->renderPage();
	}

	private function getOrRelatedFieldsGroup($fieldsGroup)
	{
		return 'related';
	}

	public function getIndependentTable(Collection $elements, $fieldsGroupsName)
	{
		$tableName = $this->getModelClassBasename();
		$fieldsGroupsName = $this->getOrRelatedFieldsGroup($fieldsGroupsName);
		$fieldsGroup = $this->getTableFieldsGroups([$fieldsGroupsName]);

		$this->table = Datatables::create(
			$tableName,
			$fieldsGroup,
			function() use($elements)
			{
				return $elements;
			},
			false,
			[],
			$this->getModelClass()
		);

		$this->table->setArrayTable();
		$this->table->setPageLength(30);
		// $this->table->setMinimalDom();

		return $this->table;
	}

	public function getIndexFieldsGroups()
	{
		return $this->indexFieldsGroups;
	}

	public function getIndexCacheKey()
	{
		return $this->indexCacheKey;
	}

	public function getPlaceholderElement()
	{
		return $this->getModelClass()::make();
	}


	public function getIndexModelIds()
	{
        $placeholder = $this->getPlaceholderElement();

        return \DB::table(
            $placeholder->getTable()
        )->select(
            $placeholder->getKeyName()
        )->get()->pluck(
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

    public function getCachedIndexElements()
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

		return $missingElements->merge($cachedModels);
    }
}