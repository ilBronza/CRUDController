<?php

namespace ilBronza\CRUD\Traits;

use Auth;
use IlBronza\Datatables\Datatables;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
// use \newdatatable;

trait CRUDIndexTrait
{
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

    	return $this->modelClass::userCanCreate(Auth::user());
    }

    private function manageCreateButton()
    {
		if(! $this->userCanCreate())
			return ;

		$createButton = $this->getCreateButton();

		$this->table->addButton($createButton);
    }

    private function addIndexButtonsToTable()
    {
    	$this->manageCreateButton();
    }

    private function getTableName()
    {
    	return Str::slug($this->getModelClassBasename());
    }

	public function _index(Request $request)
	{
		$this->table = Datatables::create(
			$this->getTableName(),
			$this->getTableFieldsGroups($this->getIndexFieldsGroups()),
			function()
			{
				return $this->getIndexElements();
			}
		);

		$this->addIndexButtonsToTable();

		return $this->table->renderPage();
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