<?php

namespace IlBronza\CRUD\Traits;

use Auth;
use IlBronza\Datatables\Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
// use \newdatatable;

trait CRUDIndexTrait
{
	public function getSelectRow()
	{
		return $this->selectRow ?? false;
	}

	/**
	 * takes all the necessary fieldsGroups by key
	 *
	 * @param string|string $fullQualifiedClass
	 * @param array|string $keys
	 *
	 * @return array
	 */
	public function getTableFieldsGroups($keys)
	{
		if(! is_array($keys))
			$keys = [$keys];

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

	public function userCanCreate()
	{
		if(! $this->methodIsAllowed('index'))
			return false;

		return $this->getModelClass()::userCanCreate(Auth::user());
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
		$this->manageCreateButton();
		$this->manageReorderButton();

		$this->addIndexButtons();
	}

	public function addIndexButtons() { }

	private function getTableName()
	{
		return Str::slug($this->getModelClassBasename());
	}

	public function beforeRenderIndex() { }

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
			$selectRow,
			$tableVariables,
			$baseModel ?? $this->getModelClass()
		);

		if(request()->ajax())
			return $this->table->renderPage();

		$this->table->addBaseModelClass($this->getModelClass());

		$this->table->setPageLength($this->getPageLength());

		if(isset($this->parentModel)&&($this->mustDisplayParentModel()))
			$this->table->addParentModel($this->parentModel);

		$this->addIndexButtonsToTable();

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
}